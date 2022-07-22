<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\DetailReturPenjualan;
use App\Models\ReturPenjualan;
use App\Http\Resources\ReturPenjualan as ReturPenjualanResource;
use App\Models\HargaBarang;
use App\Models\Barang;
use App\Models\Reference;
use App\Models\TokoNoLimit;
use Carbon\Carbon as Carbon;
use App\Http\Resources\DetailReturPenjualan as DetailReturPenjualanResource;
use App\Http\Resources\Barang as BarangResource;

class DetailReturPenjualanController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if(!$this->user->can('Menu Detail Retur Penjualan')) {
            return $this->Unauthorized();
        }

        $list_detail_retur_penjualan = DetailReturPenjualan::with('barang')->latest();
        if (!$list_detail_retur_penjualan) {
            return $this->dataNotFound('Detail Retur Penjualan');
        }

        $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 5;
        $list_detail_retur_penjualan = $perPage == 'all' ? $list_detail_retur_penjualan->get() : $list_detail_retur_penjualan->paginate((int)$perPage);
        return DetailReturPenjualanResource::collection($list_detail_retur_penjualan);
    }

    // NOTES:
    // - set default modifier (value_retur_percentage) dari 80% menjadi 50%
    // - jika punya npwp, tidak kena potongan pajak 10% dari harga barang
    // - Untuk toko MT (tim SK) nilai retur full (110% dari harga sblm pajak)
    public function store(Request $request)
    {
        if(!$this->user->can('Tambah Detail Retur Penjualan')) {
            return $this->Unauthorized();
        }

        $this->validate($request, [
            'id_retur_penjualan'=> 'required|numeric|min:0|max:9999999999',
            'id_barang'         => 'required|numeric|min:0|max:9999999999',
            'kategori_bs'       => 'required',
            'expired_date'      => 'date',
            'qty_dus'           => 'required|numeric|min:0|max:9999999999',
            'qty_pcs'           => 'required|numeric|min:0|max:9999999999',
            'disc_nominal'      => 'required|numeric|min:0',
            'disc_persen'       => 'required|numeric|min:0'
        ]);

        $retur_penjualan = ReturPenjualan::find($request->id_retur_penjualan);
        if (!$retur_penjualan) {
            return $this->dataNotFound('retur penjualan');
        }

        if ($retur_penjualan->status !== 'waiting') {
            return response()->json(['message' => 'Retur penjualan sudah disetujui'], 400);
        }

        $input                  = $request->all();
        $input['created_by']    = $this->user->id;
        $input['qty_dus_order'] = $request->qty_dus;
        $input['qty_pcs_order'] = $request->qty_pcs;
        $input['disc_nominal']  = $request->disc_nominal;
        $input['disc_persen']   = $request->disc_persen;
        $retur_penjualan        = ReturPenjualan::find($request->id_retur_penjualan);
        $toko                   = $retur_penjualan->toko;
        $tipe_harga_toko        = $toko->tipe_harga;
        $barang = Barang::find($request->id_barang);
        $npwp   = $toko->ketentuan_toko->npwp;
        $barang_mitra = $barang->id_mitra == null ? 0 : $barang->id_mitra;

        $reference         = new Reference();
        $active_date       = $reference->where('code','pembatasan_retur')->first() ?
                             Carbon::createFromFormat('Y-m-d', $reference->where('code','pembatasan_retur')->first()->value) : Carbon::now()->addDay(1);
        $batas_retur_baik  = $reference->where('code','batas_retur_baik')->first() ?
                             $reference->where('code','batas_retur_baik')->first()->value : 0;
        $batas_retur_bs    = $reference->where('code','batas_retur_bs')->first()   ?
                             $reference->where('code','batas_retur_bs')->first()->value   : 0;

        $to            = Carbon::createFromFormat('Y-m-d', $request->expired_date);
        $from          = Carbon::createFromFormat('Y-m-d', $retur_penjualan->sales_retur_date);
        $diff_in_days  = $from->diffInDays($to, false);
        $toko_no_limit = TokoNoLimit::where('tipe','toko_bebas_retur')->where('id_toko',$toko->id)->exists();
        $tim_no_limit  = $reference->where('code','tim_bebas_retur')->first();
        $is_tim_no_limit = false;

        if($tim_no_limit['value'] != '-') {
            $tim = explode(',', $tim_no_limit['value']);
            if(in_array($toko->id_tim, $tim)) {
                $is_tim_no_limit = true;
            }
        }

        if($is_tim_no_limit){
        }
        else if($toko_no_limit){
        }
        else if($barang->tipe == 'bebas_retur'){
        }
        else if($this->user->can('Tambah Detail Bebas Retur Penjualan')){
        }
        else if((Carbon::now()->diffInDays($active_date, false))<=0){
            if($barang->tipe == 'exist'){
                if(strtolower($request->kategori_bs) == 'tk' || strtolower($request->kategori_bs) == 'kr'  || strtolower($request->kategori_bs) == 'kd'){
                    return response()->json(['error' => 'Tipe retur tidak diterima, dengan tipe barang exist'], 422);
                }
            }
            if ($retur_penjualan->tipe_barang == 'bs') {
                if($barang->tipe == 'non_exist'){
                    if($diff_in_days<(0-floatval($batas_retur_bs))){ //ini akan dibuat di reference
                        return response()->json(['error' => 'Tanggal input melebihi batas expired yang diperbolehkan'], 422);
                    }
                }else{
                    if(strtolower($request->kategori_bs) == 'kd'){
                        return response()->json(['error' => 'Retur BS tidak diperbolehkan'], 422);
                    }
                }
            }
            else{
                if($diff_in_days<floatval($batas_retur_baik)){ //ini akan dibuat di reference
                    return response()->json(['error' => 'Tanggal input melebihi tanggal expired, lebih '.
                          (floatval($batas_retur_baik)-$diff_in_days).' hari'], 422);
                }
            }
        }

        if ($retur_penjualan->id_mitra <> $barang_mitra) {
            return response()->json(['error' => 'Toko dan barang mitra tidak sesuai'], 400);
        }

        if ($tipe_harga_toko != '' && $tipe_harga_toko != null) {
            $tipe_harga  = $tipe_harga_toko;
        } else {
            return response()->json(['error' => 'Tipe harga toko belum di atur, hubungi admin'], 400);
        }

        $harga_barang = HargaBarang::where('id_barang', $request->id_barang)
            ->where('tipe_harga', $tipe_harga)
            ->whereDate('created_at', '<', $retur_penjualan->sales_retur_date)
            ->latest()
            ->first();

        $harga_dbp = HargaBarang::where('id_barang', $request->id_barang)
            ->where('tipe_harga', 'dbp')
            ->whereDate('created_at', '<', $retur_penjualan->sales_retur_date)
            ->latest()
            ->first();

        if ($harga_barang === null) {
            return response()->json([
                'error' => 'Tipe harga toko belum di atur, hubungi admin'
            ], 400);
        }

        if ($harga_dbp === null) {
            return response()->json([
                'error' => 'Tipe harga toko belum di atur, hubungi admin'
            ], 400);
        }

        $input['harga'] = $harga_barang->harga / 1.1;
        $input['harga_dbp'] = $harga_dbp->harga / 1.1;
        $value_retur_percentage = 100; // modifier (dalam %)
        $input['value_retur_percentage'] = $value_retur_percentage;

        $isi = $barang->isi;
        $qty = $request->qty_dus + ($request->qty_pcs / $isi);
        $input['subtotal'] = $qty * $harga_barang->harga * ($value_retur_percentage / 100); // harga termasuk ppn

        try {
            return DetailReturPenjualan::create($input) ? $this->storeTrue('Detail retur') : $this->storeFalse('Detail retur');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function show($id)
    {
        if(!$this->user->can('Edit Detail Retur Penjualan')) {
            return $this->Unauthorized();
        }

        $detail_retur_penjualan = DetailReturPenjualan::with('barang')->find($id);
        if (!$detail_retur_penjualan) {
            return $this->dataNotFound('Detail Retur');
        }

        return new DetailReturPenjualanResource($detail_retur_penjualan);
    }

    public function detail($id){
        if(!$this->user->can('Edit Detail Retur Penjualan')) {
            return $this->Unauthorized();
        }

        $detail_retur_penjualan = DetailReturPenjualan::with('barang')
            ->where('id_retur_penjualan', $id)
            ->get()
            ->sortBy('barang.kode_barang');
        if (!$detail_retur_penjualan) {
            return $this->dataNotFound('Detail Retur');
        }

        return DetailReturPenjualanResource::collection($detail_retur_penjualan);
    }

    public function update(Request $request, $id)
    {
        if(!$this->user->can('Update Detail Retur Penjualan')) {
            return $this->Unauthorized();
        }

        $detail_retur_penjualan = DetailReturPenjualan::find($id);
        if (!$detail_retur_penjualan) {
            return $this->dataNotFound('Detail Retur');
        }

        $retur_penjualan        = ReturPenjualan::find($detail_retur_penjualan->id_retur_penjualan);
        if($retur_penjualan->status != 'waiting' && $retur_penjualan->status != 'canceled') {
            return response()->json([
                'message' => 'Anda hanya dapat mengubah data Retur Penjualan yang statusnya WAITING atau CANCELED saja.'
            ], 422);
        }

        $this->validate($request, [
            'id_barang'     => 'required|numeric|min:0|max:9999999999',
            'kategori_bs'   => 'required',
            'expired_date'  => 'date',
            'qty_dus'       => 'required|numeric|min:0|max:9999999999',
            'qty_pcs'       => 'required|numeric|min:0|max:9999999999',
            'disc_nominal'  => 'required|numeric|min:0',
            'disc_persen'   => 'required|numeric|min:0'
        ]);

        $input                          = $request->all();
        $input['id_retur_penjualan']    = $retur_penjualan->id;
        $input['updated_by']            = $this->user->id;
        $toko                           = $retur_penjualan->toko;
        $tipe_harga_toko                = $toko->tipe_harga;
        $barang = Barang::find($request->id_barang);
        $barang_mitra = $barang->id_mitra == null ? 0 : $barang->id_mitra;

        $reference         = new Reference();
        $active_date       = $reference->where('code','pembatasan_retur')->first()->value;
        $batas_retur_baik  = $reference->where('code','batas_retur_baik')->first()->value;
        $batas_retur_bs    = $reference->where('code','batas_retur_bs')->first()->value;

        $active_date   = Carbon::createFromFormat('Y-m-d', $active_date);
        $to            = Carbon::createFromFormat('Y-m-d', $request->expired_date);
        $from          = Carbon::createFromFormat('Y-m-d',  $retur_penjualan->sales_retur_date);
        $diff_in_days  = $from->diffInDays($to, false);
        $toko_no_limit = TokoNoLimit::where('tipe','toko_bebas_retur')->where('id_toko',$toko->id)->exists();
        $tim_no_limit  = $reference->where('code','tim_bebas_retur')->first();
        $is_tim_no_limit = false;

        if($tim_no_limit['value'] != '-') {
            $tim = explode(',', $tim_no_limit['value']);
            if(in_array($toko->id_tim, $tim)) {
                $is_tim_no_limit = true;
            }
        }

        if($is_tim_no_limit){
        }
        else if($toko_no_limit){
        }
        else if($barang->tipe == 'bebas_retur'){
        }
        else if($from->diffInDays($active_date, false)<=0){
            if($barang->tipe == 'exist'){
                if(strtolower($request->kategori_bs) == 'tk' || strtolower($request->kategori_bs) == 'kr' || strtolower($request->kategori_bs) == 'kd'){
                    return response()->json(['error' => 'Tipe retur tidak diterima, dengan tipe barang exist'], 422);
                }
            }
            if ($retur_penjualan->tipe_barang == 'bs') {
                if($barang->tipe == 'non_exist'){
                    if($diff_in_days<(0-floatval($batas_retur_bs))){ //ini akan dibuat di reference
                        return response()->json(['error' => 'Tanggal input melebihi batas expired yang diperbolehkan'], 422);
                    }
                }else{
                    if(strtolower($request->kategori_bs) == 'kd'){
                        return response()->json(['error' => 'Retur BS tidak diperbolehkan'], 422);
                    }
                }
            }
            else{
                if($diff_in_days<floatval($batas_retur_baik)){ //ini akan dibuat di reference
                    return response()->json(['error' => 'Tanggal input melebihi tanggal expired, lebih '.
                          (floatval($batas_retur_baik)-$diff_in_days).' hari'], 422);
                }
            }
        }

        if ($retur_penjualan->id_mitra <> $barang_mitra) {
            return response()->json(['error' => 'Toko dan barang mitra tidak sesuai'], 400);
        }

        if ($tipe_harga_toko != '' && $tipe_harga_toko != null) {
            $tipe_harga  = $tipe_harga_toko;
        } else {
            return response()->json([
                'error' => 'Tipe harga toko belum di atur, hubungi admin'
            ], 400);
        }

        $harga_barang = HargaBarang::where('id_barang', $request->id_barang)
            ->where('tipe_harga', $tipe_harga)
            ->whereDate('created_at', '<', $retur_penjualan->sales_retur_date)
            ->latest()
            ->first();

        $harga_dbp = HargaBarang::where('id_barang', $request->id_barang)
            ->where('tipe_harga', 'DBP')
            ->whereDate('created_at', '<', $retur_penjualan->sales_retur_date)
            ->latest()
            ->first();

        if ($harga_barang === null) {
            return response()->json([
                'error' => 'Tipe harga tidak ditemukan, hubungi admin'
            ], 400);
        }

        if ($harga_dbp === null) {
            return response()->json([
                'error' => 'Harga dbp belum diatur, hubungi admin'
            ], 400);
        }

        $input['harga'] = $harga_barang->harga / 1.1;
        $input['harga_dbp'] = $harga_dbp->harga / 1.1;
        $value_retur_percentage = 100; // modifier (dalam %)
        $input['value_retur_percentage'] = $value_retur_percentage;

        $isi = $barang->isi;
        $qty = $request->qty_dus + ($request->qty_pcs / $isi);
        $input['subtotal'] = $qty * $harga_barang->harga * $value_retur_percentage / 100; // harga termasuk ppn

        return $detail_retur_penjualan->update($input) ? $this->updateTrue('Detail retur') : $this->updateFalse('Detail retur');
    }

    public function destroy($id)
    {
        if(!$this->user->can('Hapus Detail Retur Penjualan')) {
            return $this->Unauthorized();
        }

        $detail_retur_penjualan = DetailReturPenjualan::find($id);
        if (!$detail_retur_penjualan) {
            return $this->dataNotFound('Detail retur');
        }

        $retur_penjualan = ReturPenjualan::find($detail_retur_penjualan->id_retur_penjualan);

        if($retur_penjualan->status != 'waiting' && $retur_penjualan->status != 'canceled') {
            return response()->json([
                'message' => 'Anda hanya dapat menghapus data Barang pada Retur yang statusnya WAITING atau CANCELED saja.'
            ], 422);
        }

        return $detail_retur_penjualan->delete() ?
            $this->destroyTrue('detail retur') :
            $this->destroyFalse('detail retur');
    }


    public function list_barang(Request $request)
    {
        $id_barang_sudah = DetailReturPenjualan::where('id_retur_penjualan', $request->id_retur_penjualan)
            ->pluck('id_barang')->toArray();
        $list_barang    = Barang::get()->except($id_barang_sudah)->sortBy('kode_barang');
        return BarangResource::collection($list_barang);
    }

}

<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\HargaBarang;
use App\Models\Reference;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\JWTAuth;
use App\Models\ReturPenjualan;
use App\Models\DetailReturPenjualan;
use App\Models\RiwayatSaldoRetur;
use App\Models\Salesman;
use App\Models\Toko;
use App\Models\KetentuanToko;
use App\Models\TokoNoLimit;
use App\Models\Stock;
use App\Models\PosisiStock;
use App\Helpers\Helper;
use App\Http\Resources\ReturPenjualan as ReturPenjualanResource;
use App\Http\Resources\ReturPenjualanPrint as ReturPenjualanPrintResource;
use Carbon\Carbon as Carbon;

class ReturPenjualanController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    // NOTES :
    // - tidak ada tukar guling, pakai potong nota dgn persentase penurunal nilai 80% -> NON-NPWP, yg ad -> NPWP 100%
    // - jika barang yg di retur baik, masuk ke gudang baik/canvas
    // - jika barang badstock masuk ke gudang bs

    public function index(Request $request) // parameter : id_salesman, start_date, end_date, per_page, page
    {
        if (!$this->user->can('Menu Retur Penjualan')) {
            return $this->Unauthorized();
        }

        $id_user = $this->user->id;
        $keyword = $request->keyword;
        $status  = $request->status ?? 'all';
        $id_depo = $request->depo ?? [];
        $id_mitra= $request->has('id_mitra') && $request->id_mitra <> '' ? $request->id_mitra : 'include';
        $jenis_retur = $request->has('jenis_retur') ? $request->jenis_retur : 'all';

        $list_retur_penjualan = ReturPenjualan::with('toko', 'ketentuan_toko', 'toko.mitra', 'salesman.user', 'salesman.tim.depo', 'gudang', 'user_verify')
        ->when($jenis_retur <> 'all', function ($q) use ($jenis_retur){
            return $q->where('tipe_barang',$jenis_retur);
        })
        ->when($keyword <> '', function ($q) use ($keyword) {
            $q->where( function ($q) use ($keyword) {
                $q->where('id', 'like', "%{$keyword}%")
                    ->orWhereHas('toko', function ($q) use ($keyword) {
                        $q->where('nama_toko', 'like', "%{$keyword}%")
                            ->orWhere('no_acc', 'like', "%{$keyword}%")
                            ->orWhere('cust_no', 'like', "%{$keyword}%");
                    });
            });
        })
        ->when($id_mitra <> 'exclude' && $id_mitra <> 'include', function ($q) use ($id_mitra) {
            $q->where('id_mitra', $id_mitra);
        })
        ->when($id_mitra == 'exclude', function ($q) {
            $q->where('id_mitra', '=', 0);
        })
        ->latest();

        if ($this->user->can('Retur Penjualan Salesman')) {
            $list_retur_penjualan = $list_retur_penjualan->where('id_salesman', $id_user);
        }

        if ($this->user->can('Retur Penjualan Tim')) {
            $id_salesman_supervisor = Helper::salesBySupervisor($id_user);
            $list_retur_penjualan   = $list_retur_penjualan->whereIn('id_salesman', $id_salesman_supervisor);
        }

        if ($this->user->can('Retur Penjualan Tim Koordinator')) {
            $id_salesman_koordinator= Helper::salesByKoordinator($id_user);
            $list_retur_penjualan   = $list_retur_penjualan->whereIn('id_salesman', $id_salesman_koordinator);
        }

        if ($this->user->can('Retur Penjualan Logistik')) {
            $list_retur_penjualan = $list_retur_penjualan->whereNotIn('status', ['waiting', 'canceled']);
        }

        if ($status <> 'all') {
            if ($status == 'claim') {
                $list_retur_penjualan = $list_retur_penjualan->whereNotNull('claim_date');
            } else {
                $list_retur_penjualan = $list_retur_penjualan->whereStatus($status);
            }
        }

        if(count($id_depo) != 0){
            $id_depo = $request->depo;
        } else {
            $id_perusahaan  = $request->has('id_perusahaan') && $request->id_perusahaan ? [$request->id_perusahaan] : null;
            $id_depo        = Helper::depoIDByUser($this->user->id, $id_perusahaan);
        }

        $list_retur_penjualan = $list_retur_penjualan->whereIn('id_depo', $id_depo);

        $id_salesman = $request->id_salesman ?? 'all';
        if($id_salesman <> 'all' && $id_salesman <> ''){
            $list_retur_penjualan = $list_retur_penjualan->where('id_salesman', $id_salesman);
        }

        if($request->has(['start_date', 'end_date']) && $request->claim_date == ''){
            if ($status == 'claim') {
                $list_retur_penjualan = $list_retur_penjualan->whereBetween('claim_date', [$request->start_date, $request->end_date]);
            } else {
                $list_retur_penjualan = $list_retur_penjualan->whereBetween('sales_retur_date', [$request->start_date, $request->end_date]);
            }
        }

        if ($request->claim_date <> '') {
            $list_retur_penjualan = $list_retur_penjualan->where('claim_date', $request->claim_date);
        }

        $perPage = $request->has('per_page') ?
            $perPage = $request->per_page : $perPage = 5;
        $list_retur_penjualan = $perPage == 'all' ?
            $list_retur_penjualan->get() : $list_retur_penjualan->paginate((int)$perPage);

        if ($list_retur_penjualan) {
            return ReturPenjualanResource::collection($list_retur_penjualan);
        }

        return response()->json([
            'message' => 'Data Retur Penjualan tidak ditemukan!'
        ], 404);
    }

    public function store(Request $request)
    {
        if (!$this->user->can('Tambah Retur Penjualan')) {
            return $this->Unauthorized();
        }

        $this->validate($request, [
            'id_toko' => 'required|numeric|min:1|max:9999999999',
            'tipe_barang' => 'required|in:bs,baik,sample',
        ]);

        $user_id= $this->user->id;
        $input  = $request->all();
        if (!$request->has('id_salesman')) {
            $input['id_salesman']   = $user_id;
        }
        $input['tipe_retur']        = 'credit_note'; // tidak ada tukar guling lagi, semua potong nota
        $input['status']            = 'waiting';
        $input['sales_retur_date']  = Carbon::now()->toDateString();
        $input['created_by']        = $user_id;

        $salesman   = Salesman::find($input['id_salesman']);
        $depo       = $salesman->tim->depo;
        $tipe_tim   = $salesman->tim->tipe;
        if($request->tipe_barang == 'baik' && $tipe_tim == 'canvass'){
            $input['id_gudang'] = $salesman->tim->canvass->id_gudang_canvass;
        } else {
            $depo_khusus = Reference::where('code', '=', 'depo_retur_baik')->first();
            $depo_khusus = explode(',', $depo_khusus['value']);
            if ($request->tipe_barang == 'baik' && !in_array($depo->id, $depo_khusus)) {
                $id_gudang = $depo->id_gudang;
            } else {
                $id_gudang = $depo->id_gudang_bs;
            }
            $input['id_gudang'] = $id_gudang;
        }

        $input['id_depo'] = $depo->id;
        $input['id_tim']  = $salesman->id_tim;

        // POTONGAN 10% UNTUK NON PKP
        $toko = Toko::find($input['id_toko']);
        $npwp = $toko->ketentuan_toko->npwp ?? '';
        $potongan = 0;
        if ($npwp == '' || $npwp == null) {
            $potongan = 10;
        } else {
            $input['npwp'] = $npwp;
        }
        $input['potongan'] = $potongan;

        try {
            $retur_penjualan = ReturPenjualan::create($input);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        $new_retur_penjualan = ReturPenjualan::with([
            'toko',
            'ketentuan_toko',
            'salesman.user',
            'salesman.tim.depo',
            'gudang'
        ])->find($retur_penjualan->id);

        return response()->json([
            'message' => 'Data Retur Penjualan berhasil disimpan.',
            'data' => new ReturPenjualanResource($new_retur_penjualan)
        ], 201);
    }

    public function show($id)
    {
        if (!$this->user->can('Edit Retur Penjualan')) {
            return $this->Unauthorized();
        }

        $retur_penjualan = ReturPenjualan::with([
            'toko',
            'ketentuan_toko',
            'salesman.user',
            'salesman.tim.depo',
            'gudang',
            'user_verify'
        ])->find($id);

        if ($retur_penjualan) {
            return new ReturPenjualanResource($retur_penjualan);
        }

        return $this->dataNotFound('retur penjualan');
    }

    public function update(Request $request, $id)
    {
        if(!$this->user->can('Update Retur Penjualan')) {
            return $this->Unauthorized();
        }

        $retur_penjualan = ReturPenjualan::find($id);
        if (!$retur_penjualan) {
            return $this->dataNotFound('Retur penjualan');
        }

        if($retur_penjualan->status != 'waiting' && $retur_penjualan->status != 'canceled') {
            return response()->json([
                'message' => 'Anda hanya dapat mengubah data Retur Penjualan yang statusnya WAITING atau CANCELED saja.'
            ], 422);
        }

        $this->validate($request, [
            'id_toko'           => 'required|numeric|min:0|max:9999999999',
            'sales_retur_date'  => 'required|date',
            'tipe_barang'       => 'required|in:bs,baik,sample'
        ]);

        if ($request->has('id_salesman') && $request->id_salesman <> '') {
            $retur_penjualan->id_salesman = $request->id_salesman;
        }

        $input                  = $request->all();
        $input['id_salesman']   = $retur_penjualan->id_salesman;
        $input['status']        = $retur_penjualan->status;
        $input['updated_by']    = $this->user->id;
        $salesman               = Salesman::find($retur_penjualan->id_salesman);
        $depo                   = $salesman->tim->depo;
        $tipe_tim               = $salesman->tim->tipe;

        if($request->tipe_barang == 'baik' && $tipe_tim == 'canvass'){
            $input['id_gudang'] = $salesman->tim->canvass->id_gudang_canvass;
        } else {
            $depo_khusus = Reference::where('code', '=', 'depo_retur_baik')->first();
            $depo_khusus = explode(',', $depo_khusus['value']);
            if ($request->tipe_barang == 'baik' && !in_array($depo->id, $depo_khusus)) {
                $id_gudang = $depo->id_gudang;
            } else {
                $id_gudang = $depo->id_gudang_bs;
            }
            $input['id_gudang'] = $id_gudang;
        }

        $input['id_tim']  = $salesman->id_tim;
        $retur_penjualan->update($input);
        $new_retur_penjualan = ReturPenjualan::with([
            'toko',
            'ketentuan_toko',
            'salesman.user',
            'salesman.tim.depo',
            'gudang'
        ])->find($retur_penjualan->id);

        return response()->json([
            'message' => 'Data Retur Penjualan telah berhasil diubah.',
            'data' => new ReturPenjualanResource($new_retur_penjualan)
        ], 201);
    }

    public function destroy($id,Request $request)
    {
        if(!$this->user->can('Hapus Retur Penjualan')) {
            return $this->Unauthorized();
        }

        $retur_penjualan = ReturPenjualan::find($id);
        if (!$retur_penjualan) {
            return $this->dataNotFound('retur penjualan');
        }

        if($retur_penjualan->status != 'waiting' && $retur_penjualan->status != 'canceled') {
            return response()->json([
                'message' => 'Anda hanya dapat menghapus data Retur Penjualan yang statusnya WAITING atau CANCELED saja.'
            ], 422);
        }

        $data = ['deleted_by' => $this->user->id];
        $retur_penjualan->update($data);
        return $retur_penjualan->delete() ? $this->destroyTrue('Retur Penjualan') :  $this->destroyFalse('Retur Penjualan');
    }

    public function restore($id)
    {
        if(!$this->user->can('Tambah Retur Penjualan')) {
            return $this->Unauthorized();
        }

        $retur_penjualan = ReturPenjualan::withTrashed()->find($id);
        if (!$retur_penjualan) {
            return $this->dataNotFound('Retur Penjualan');
        }

        $data = ['deleted_by' => null];
        $retur_penjualan->update($data);
        $retur_penjualan->restore();

        return response()->json([
            'message' => 'Data Retur Penjualan berhasil dikembalikan.'
        ], 200);
    }

    // fungsi cek sisa saldo retur -> TokoController@get_saldo_retur

    // fungsi riwayat saldo retur

    // fungsi APPROVE
    // user: gudang atau edp
    // set approved_by
    // set status = approved
    // calculate saldo_retur
    // create riwayat_retur_penjualan, set id_toko, saldo_awal, saldo_akhir, keterangan, id_retur_penjualan
    // saldo_awal = KetentuanToko::find($id_toko)->saldo_retur
    // saldo_akhir = $saldo_awal + $retur_penjualan->saldo_retur
    // keterangan
    // retur yg sudah di approve tdk dpt diubah dan dicancel

    // fungsi CLAIM
    // user salesman @ fungsi tambah penjualan
    // set claim_date (CANCEL)
    // set id_penjualan (CANCEL), potong nota sesuai dengan saldo retur


    public function approve($id){
        if(!$this->user->can('Approve Retur Penjualan')) {
            return $this->Unauthorized();
        }

        $retur_penjualan = ReturPenjualan::find($id);

        if (!$retur_penjualan) {
            return $this->dataNotFound('Retur Penjualan');
        }

        if($retur_penjualan->status != 'waiting') {
            return response()->json([
                'message' => 'Retur Penjualan telah disetujui.'
            ], 422);
        }

        if (Helper::returValueLimit($retur_penjualan->id_tim,$retur_penjualan->id_toko)) {
            return response()->json([
                'error' => 'Retur Melebihi limit retur, yang diperbolehkan dalam satu bulan'
            ], 422);
        }

        if ($this->user->can('Approve Retur Penjualan Khusus')){
            if($this->user->salesman->tim->tipe == 'canvass'){
                if($this->user->id != $retur_penjualan->id_salesman){
                    return $this->Unauthorized();
                }
            }
            else{
                return $this->Unauthorized();
            }
        }

        $detail_retur_penjualan = DetailReturPenjualan::join('barang','id_barang','barang.id')->where('id_retur_penjualan', $id)->get();

        if($detail_retur_penjualan->count() <= 0) {
            return response()->json([
                'message' => 'Data Retur Penjualan masih kosong, isi barang terlebih dahulu.'
            ], 422);
        }

        $total_saldo_retur  = $retur_penjualan->grand_total;
        $data['status']     = 'approved';
        $data['saldo_retur']= $total_saldo_retur;
        $data['approved_by']= $this->user->id;
        $data['approved_at']= Carbon::now()->toDateTimeString();

        // ======================= GENERATE NOMOR INVOICE =======================
        $kode_depo  = $retur_penjualan->salesman->tim->depo->kode_depo;
        $today      = Carbon::today()->format('dmy');

        $keyword        = '.' . $today . '.' . $kode_depo;
        $list_no_invoice= \DB::table('retur_penjualan')->where('no_invoice', 'like','%' . $keyword)->pluck('no_invoice');

        if(count($list_no_invoice) == 0){
            $string_no = '00001';
        }else{
            $arr = [];
            foreach ($list_no_invoice as $value) {
                array_push($arr, (int)substr($value, strrpos($value, '-') + 1));
            };
            $new_no = max($arr)+1;
            $string_no = sprintf("%05d", $new_no);
        }

        $data['no_invoice'] = $string_no . '.' . $today . '.' . $kode_depo;
        $reference         = new Reference();
        $active_date       = $reference->where('code','pembatasan_retur')->first() ?
                             Carbon::createFromFormat('Y-m-d', $reference->where('code','pembatasan_retur')->first()->value) : Carbon::now()->addDay(1);
        $batas_retur_baik  = $reference->where('code','batas_retur_baik')->first() ?
                             $reference->where('code','batas_retur_baik')->first()->value : 0;
        $batas_retur_bs    = $reference->where('code','batas_retur_bs')->first()   ?
                             $reference->where('code','batas_retur_bs')->first()->value   : 0;
        $toko_no_limit     = TokoNoLimit::where('tipe','toko_bebas_retur')->where('id_toko',$retur_penjualan->id_toko)->first();
        $tim_no_limit      = $reference->where('code','tim_bebas_retur')->first();
        $is_tim_no_limit   = false;

        if($tim_no_limit['value'] != '-') {
            $tim = explode(',', $tim_no_limit['value']);
            if(in_array($retur_penjualan->toko->id_tim, $tim)) {
                $is_tim_no_limit = true;
            }
        }

        // CREATE STOCK IF NOT EXISTS
        foreach ($detail_retur_penjualan as $drp) {
            $to            = Carbon::createFromFormat('Y-m-d', $drp->expired_date);
            $from          = Carbon::createFromFormat('Y-m-d', $retur_penjualan->sales_retur_date);
            $diff_in_days  = $from->diffInDays($to, false);

            if($retur_penjualan->tipe_barang=='bs' && $drp->kategori_bs==''){
                return response()->json([
                    'error' => 'Data Retur Invalid'
                ], 422);
            }

            if($is_tim_no_limit){
            }
            else if($toko_no_limit){
            }
            else if($drp->tipe == 'bebas_retur'){
            }
            else if($this->user->can('Approve Bebas Retur Penjualan')){
            }
            else if(($from->diffInDays($active_date, false))<=0){
                if($drp->tipe=='exist'){
                    if($drp->kategori_bs == 'tk' || $drp->kategori_bs == 'kr' || $drp->kategori_bs == 'kd'){
                        return response()->json([
                            'error' => 'Tipe retur tidak diterima, dengan tipe barang exist'
                        ], 422);
                    }
                }
                if ($retur_penjualan->tipe_barang == 'bs') {
                    if($drp->tipe == 'non_exist'){
                        if($diff_in_days<(0-floatval($batas_retur_bs))){ //ini akan dibuat di reference
                            return response()->json(['error' => 'Tanggal input melebihi batas expired yang diperbolehkan'], 422);
                        }
                    }else{
                        if(strtolower($drp->kategori_bs) == 'kd'){
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


            $stock = Stock::where('id_gudang', $retur_penjualan->id_gudang)->where('id_barang', $drp->id_barang)->first();
            if ($stock === null) {
                Stock::create([
                    'id_gudang' => $retur_penjualan->id_gudang,
                    'id_barang' => $drp->id_barang,
                    'qty' => 0,
                    'qty_pcs' => 0,
                    'created_by' => $this->user->id
                ]);
            }
        }

        foreach($detail_retur_penjualan as $drp) {
            $stock = Stock::where('id_gudang', $retur_penjualan->id_gudang)->where('id_barang', $drp->id_barang)->first();
            $posisi_stock = PosisiStock::where('id_stock', $stock->id)->where('tanggal', Carbon::today()->toDateString())->first();
            if(!$posisi_stock) {
                PosisiStock::create([
                    'id_stock' => $stock->id,
                    'tanggal' => Carbon::today()->toDateString(),
                    'harga' => $stock->dbp,
                    'saldo_awal_qty' => $stock->qty,
                    'saldo_awal_pcs' => $stock->qty_pcs,
                    'saldo_akhir_qty' => $stock->qty,
                    'saldo_akhir_pcs' => $stock->qty_pcs,
                ]);
            }
        }

        DB::beginTransaction();
        try {
            $retur_penjualan->update($data);

            $ketentuan_toko = KetentuanToko::find($retur_penjualan->id_toko);

            $saldo_awal = $ketentuan_toko->saldo_retur;

            $saldo_akhir = $saldo_awal + $total_saldo_retur;

            $ketentuan_toko->increment('saldo_retur', $total_saldo_retur);

            $toko = Toko::find($retur_penjualan->id_toko);

            $data_saldo_retur['id_toko']        = $retur_penjualan->id_toko;
            $data_saldo_retur['saldo_awal']     = $saldo_awal;
            $data_saldo_retur['saldo_akhir']    = $saldo_akhir;
            $data_saldo_retur['keterangan']     = $toko->nama_toko . ' menerima saldo retur sebesar Rp. ' . number_format($total_saldo_retur, 0, ',', '.') . ' dari Retur Penjualan no ' . $id . '.';
            $data_saldo_retur['id_retur_penjualan'] = $id;

            RiwayatSaldoRetur::create($data_saldo_retur);
            $logStock = [];
            // ==================================== KEMBALIKAN BARANG KE GUDANG BAIK / BS ====================================
            foreach($detail_retur_penjualan as $drp) {
                $stock = Stock::where('id_gudang', $retur_penjualan->id_gudang)->where('id_barang', $drp->id_barang)->first();
                if ($stock === null) {
                    throw new ModelNotFoundException('Stock tidak ditemukan, hubungi IT Support');
                }

                $posisi_stock = PosisiStock::where('id_stock', $stock->id)->where('tanggal', Carbon::today()->toDateString())->first();
                if ($posisi_stock === null) {
                    throw new ModelNotFoundException('Posisi stock tidak ditemukan, hubungi IT Support');
                }

                // tambah stock gudang
                $stock->increment('qty', $drp->qty_dus);
                $stock->increment('qty_pcs', $drp->qty_pcs);

                // catat riwayat pergerakan stock
                $posisi_stock->increment('mutasi_masuk_qty', $drp->qty_dus);
                $posisi_stock->increment('mutasi_masuk_pcs', $drp->qty_pcs);
                $posisi_stock->increment('saldo_akhir_qty', $drp->qty_dus);
                $posisi_stock->increment('saldo_akhir_pcs', $drp->qty_pcs);

                $logStock[] = [
                    'tanggal'       => Carbon::now()->toDateString(),
                    'id_barang'     => $stock->id_barang,
                    'id_gudang'     => $stock->id_gudang,
                    'id_user'       => $this->user->id,
                    'id_referensi'  => $drp->id,
                    'referensi'     => 'retur',
                    'no_referensi'  => $id,
                    'qty_pcs'       => ($drp->qty_dus * $stock->isi) + $drp->qty_pcs,
                    'status'        => 'approved',
                    'created_at'    => Carbon::now(),
                    'updated_at'    => Carbon::now()
                ];
            }

            $logData = [
                'action' => 'Approve Retur Penjualan',
                'description' => 'Retur: ' .$retur_penjualan->id,
                'user_id' => $this->user->id
            ];

            $this->log($logData);
            $this->createLogStock($logStock);
            DB::commit();
            return response()->json([
                'message' => 'Data Retur Penjualan berhasil disetujui.',
                'data_saldo_retur' => $total_saldo_retur
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyetujui retur penjualan'
            ], 400);
        }
    }

    public function unapprove($id)
    {
        if (!$this->user->can('Unapprove Retur Penjualan')) {
            return $this->Unauthorized();
        }

        $retur = ReturPenjualan::find($id);
        if (!$retur) {
            return $this->dataNotFound('Retur Penjualan');
        }

        if ($retur->status !== 'approved') {
            return response()->json(['message' => 'retur status '.$retur->status.' tidak bisa dibatalkan'], 400);
        }

        if ($retur->claim_date !== null) {
            return response()->json(['message' => 'Retur sudah diklaim tidak boleh dibatalkan'], 400);
        }

        $details = $retur->detail_retur_penjualan;
        $ketentuan_toko = KetentuanToko::where('id_toko', $retur->id_toko)->first();

        foreach ($details as $detail) {
            $stock = Stock::where('id_gudang', $retur->id_gudang)->where('id_barang', $detail->id_barang)->first();
            $posisi_stock = PosisiStock::where('id_stock', $stock->id)
                ->where('tanggal', Carbon::today()->toDateString())->first();
            if(!$posisi_stock) {
                PosisiStock::create([
                    'id_stock' => $stock->id,
                    'tanggal' => Carbon::today()->toDateString(),
                    'harga' => $stock->dbp,
                    'saldo_awal_qty' => $stock->qty,
                    'saldo_awal_pcs' => $stock->qty_pcs,
                    'saldo_akhir_qty' => $stock->qty,
                    'saldo_akhir_pcs' => $stock->qty_pcs,
                ]);
            }
        }

        DB::beginTransaction();
        try {
            $ketentuan_toko->decrement('saldo_retur', $retur->saldo_retur);
            foreach ($details as $detail) {
                $stock = Stock::where('id_gudang', $retur->id_gudang)->where('id_barang', $detail->id_barang)->first();
                if ($stock === null) {
                    throw new ModelNotFoundException('Stock tidak ditemukan, hubungi IT Support');
                }

                $posisi_stock = PosisiStock::where('id_stock', $stock->id)->where('tanggal', Carbon::today()->toDateString())->first();
                if ($posisi_stock === null) {
                    throw new ModelNotFoundException('Posisi stock tidak ditemukan, hubungi IT Support');
                }

                // kurangi stock gudang
                $stock->decrement('qty', $detail->qty_dus);
                $stock->decrement('qty_pcs', $detail->qty_pcs);
                while ($stock->qty_pcs < 0) {
                    $stock->decrement('qty');
                    $stock->increment('qty_pcs', $stock->isi);
                }

                $posisi_stock->increment('mutasi_keluar_qty', $detail->qty_dus);
                $posisi_stock->increment('mutasi_keluar_pcs', $detail->qty_pcs);
                $posisi_stock->decrement('saldo_akhir_qty', $detail->qty_dus);
                $posisi_stock->decrement('saldo_akhir_pcs', $detail->qty_pcs);
                while($posisi_stock->saldo_akhir_pcs < 0) {
                    $posisi_stock->decrement('saldo_akhir_qty');
                    $posisi_stock->increment('saldo_akhir_pcs', $stock->isi);
                }
            }

            $retur->status = 'waiting';
            $retur->save();

            $this->deleteLogStock([
                ['referensi', 'retur'],
                ['no_referensi', $retur->id],
                ['status', 'approved']
            ]);

            RiwayatSaldoRetur::where('id_retur_penjualan', $retur->id)->delete();

            $logData = [
                'action' => 'Unapprove Retur Penjualan',
                'description' => 'Retur: ' .$retur->id,
                'user_id' => $this->user->id
            ];

            $this->log($logData);
            DB::commit();
            return response()->json(['message' => 'Return penjualan berhasil di batalkan'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Return gagal dibatalkan '.$e], 400);
        }
    }

    public function setClaim(Request $request, $id)
    {
        if (!$this->user->can('Tandai Klaim Retur')) {
            return $this->Unauthorized();
        }

        $validate = ['claim_date' => 'required|date'];
        $retur_penjualan = ReturPenjualan::with(['ketentuan_toko'])->find($id);

        if($retur_penjualan->ketentuan_toko->npwp!='' && $retur_penjualan->ketentuan_toko->npwp!=null){
            $validate['faktur_pajak'] = 'required';
        }
        if (!$retur_penjualan) {
            return $this->dataNotFound('Retur Penjualan');
        }

        $messages = [
            'claim_date.required' => 'Tanggal klaim wajib isi',
            'faktur_pajak.required' => 'Faktur pajak retur wajib isi'
        ];

        $this->validate($request, $validate, $messages);

        try {
            $input = [
                'claim_date'   => $request->claim_date,
                'faktur_pajak' => $request->faktur_pajak,
            ];

            if($request->faktur_pajak == '0' && $retur_penjualan->sales_retur_date >= '2021-07-05') {
                $input['potongan'] = 10;
            }

            $retur_penjualan->update($input);
            $logData = [
                'action' => 'Klaim Retur Penjualan',
                'description' => 'Retur: ' .$id.' Faktur pajak '.$request->faktur_pajak,
                'user_id' => $this->user->id
            ];
            $this->log($logData);
            return $this->updateTrue('retur penjualan');

        } catch (Exception $e) {
            return $this->updateFalse('retur penjualan');
        }
    }

    public function cancelClaim($id)
    {
        if ($this->user->can('Batalkan Klaim Retur')):
            $retur_penjualan = ReturPenjualan::find($id);
            if (!$retur_penjualan) {
                return response()->json([
                    'message' => 'Retur Penjualan tidak ditemukan.'
                ], 404);
            }
            $input = [
                'claim_date'   => null,
                'faktur_pajak' => '',
            ];

            if($retur_penjualan->npwp != '' && $retur_penjualan->npwp != null) {
                $input['potongan'] = 0;
            }

            $retur_penjualan->update($input);
            return $retur_penjualan->save() ? $this->updateTrue('retur penjualan') : $this->updateFalse('retur penjualan');
        else:
            return $this->Unauthorized();
        endif;
    }

    public function fixSaldoRetur($id)
    {
        $retur = ReturPenjualan::find($id);
        if ($retur) {
            if ($retur->status == 'approved') {
                DB::beginTransaction();
                try {
                    $ketentuan_toko = KetentuanToko::where('id_toko', $retur->id_toko)->first();
                    $ketentuan_toko->decrement('saldo_retur', $retur->saldo_retur);
                    $details = $retur->detail_retur_penjualan;
                    $grand_total = 0;
                    foreach ($details as $detail) {
                        $barang = Barang::find($detail->id_barang);
                        $harga = HargaBarang::where('id_barang', $detail->id_barang)->where('tipe_harga', 'hcobp')->latest()->first();
                        $updateDetail = [
                            'harga' =>  $harga['harga'] / 1.1,
                            'subtotal' => ($detail->qty_dus + ($detail->qty_pcs / $barang->isi)) * $harga['harga']
                        ];

                        $detail->update($updateDetail);
                        $grand_total += ($detail->qty_dus + ($detail->qty_pcs / $barang->isi)) * $harga['harga'];
                    }

                    $retur->update(['saldo_retur' => $grand_total]);
                    $ketentuan_toko->increment('saldo_retur', $grand_total);
                    DB::commit();
                    return response()->json('update berhasil', 200);
                } catch (\Exception $e) {
                    DB::rollBack();
                    dd($e->getMessage());
                }
            } else {
                DB::beginTransaction();
                try {
                    $details = $retur->detail_retur_penjualan;
                    $grand_total = 0;
                    foreach ($details as $detail) {
                        $barang = Barang::find($detail->id_barang);
                        $harga = HargaBarang::where('id_barang', $detail->id_barang)->where('tipe_harga', 'hcobp')->latest()->first();
                        $updateDetail = [
                            'harga' =>  $harga['harga'] / 1.1,
                            'subtotal' => ($detail->qty_dus + ($detail->qty_pcs / $barang->isi)) * $harga['harga']
                        ];
                        $detail->update($updateDetail);
                        $grand_total += ($detail->qty_dus + ($detail->qty_pcs / $barang->isi)) * $harga['harga'];
                    }

                    $retur->update(['saldo_retur' => $grand_total]);
                    DB::commit();
                    return response()->json('update berhasil', 200);
                } catch (\Exception $e) {
                    DB::rollBack();
                    dd($e->getMessage());
                }
            }
        } else {
            return response()->json('Data retur tidak ditemukan', 404);
        }
    }

    public function klaim_retur(Request $request) // parameter : id_salesman, start_date, end_date, per_page, page
    {
        if (!$this->user->can('Laporan Klaim Retur')) {
            return $this->Unauthorized();
        }

        $id_user = $this->user->id;
        $keyword = $request->keyword;
        $status  = $request->status ?? 'all';

        $list_retur_penjualan = ReturPenjualan::with(['toko', 'ketentuan_toko', 'salesman.user', 'salesman.tim.depo', 'gudang', 'depo.perusahaan', 'salesman'])
                                    ->whereNotNull('claim_date')
                                    ->orderBy('id', 'DESC');

        $id_salesman = $request->id_salesman ?? 'all';
        if($id_salesman <> 'all' && $id_salesman <> ''){
            $list_retur_penjualan = $list_retur_penjualan->where('id_salesman', $id_salesman);
        }

        if ($request->has(['start_date', 'end_date'])) {
            $list_retur_penjualan = $list_retur_penjualan->whereBetween('claim_date', [$request->start_date.' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        if($request->depo != null){
            $id_depo = $request->depo;
        } else {
            $id_perusahaan  = $request->has('id_perusahaan') && $request->id_perusahaan ? [$request->id_perusahaan] : null;
            $id_depo = Helper::depoIDByUser($this->user->id, $id_perusahaan);
        }
        $list_retur_penjualan = $list_retur_penjualan->whereIn('id_depo', $id_depo);


        $list_retur_penjualan = $list_retur_penjualan->when($keyword <> '', function ($q) use ($keyword) {
            $q->where('id', 'like', "%{$keyword}%")
                ->orWhereHas('toko', function ($q) use ($keyword) {
                    $q->where('nama_toko', 'like', "%{$keyword}%")
                        ->orWhere('no_acc', 'like', "%{$keyword}%")
                        ->orWhere('cust_no', 'like', "%{$keyword}%");
                });
        });

        $perPage = $request->has('per_page') ?
            $perPage = $request->per_page : $perPage = 5;
        $list_retur_penjualan = $perPage == 'all' ?
            $list_retur_penjualan->get() : $list_retur_penjualan->paginate((int)$perPage);

        if ($list_retur_penjualan) {
            return ReturPenjualanResource::collection($list_retur_penjualan);
        }

        return $this->dataNotFound('klaim retur');
    }

    public function verify_retur($id)
    {
        if (!$this->user->can('Verify Retur Penjualan')) {
            return $this->Unauthorized();
        }

        $retur_penjualan = ReturPenjualan::find($id);
        if (!$retur_penjualan) {
            return $this->dataNotFound('retur penjualan');
        }

        if ($retur_penjualan->verified_by) {
            return response()->json(['message' => 'retur penjualan sudah diverifikasi'], 400);
        }

        $update_data = [
            'verified_by' => $this->user->id,
            'verified_at' => Carbon::now()
        ];

        return $retur_penjualan->update($update_data) ? $this->updateTrue('retur penjualan') : $this->updateFalse('retur penjualan');
    }

    public function set_faktur_pajak_pembelian(Request $request, $id)
    {
        $this->validate($request, [
            'id' => 'required|exists:retur_penjualan,id',
            'faktur_pajak_pembelian'          => 'required',
            'tanggal_faktur_pajak_pembelian'  => 'required|date'
        ]);
        $input  = $request->only(['tanggal_faktur_pajak_pembelian', 'faktur_pajak_pembelian']);
        try {
            $retur_penjualan = ReturPenjualan::find($id);
            $update_retur_penjualan = $retur_penjualan->update($input);
            return response()->json(['message' => 'Set Faktur Pajak Pembelian Berhasil'], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Set Faktur Pajak Pembelian Gagal'], 400);
        }
    }

    public function retur_penjualan_print($id)
    {
        try {
            $retur_penjualan = ReturPenjualan::with([
            'toko',
            'ketentuan_toko',
            'salesman.user',
            'salesman.tim.depo',
            'gudang',
            'user_verify',
            'detail_retur_penjualan'
            ])->find($id);
            return new ReturPenjualanPrintResource($retur_penjualan);
        } catch (Exception $e) {
            return $this->dataNotFound('retur penjualan');
        }
    }
}

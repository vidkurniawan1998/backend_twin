<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Pengiriman;
use App\Http\Resources\Pengiriman as PengirimanResource;
use App\Models\Penjualan;
use App\Http\Resources\PenjualanPengiriman as PenjualanPengirimanResource;
use App\Models\DetailPenjualan;
use App\Models\DetailPengeluaranBarang;

class PengirimanController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    // GET LIST GUDANG BAIK
    // sort parameter : id_gudang, driver, kendaraan, start_date, end_date
    // web : http://localhost:8000/pengiriman?per_page=5&page=1&id_driver=&id_gudang=&id_kendaraan=&start_date=2019-07-04&end_date=2019-07-04
    // android : http://localhost:8000/pengiriman?start_date=2019-07-04&end_date=2019-07-04
    public function index(Request $request)
    {
        $list_pengiriman = Pengiriman::withCount('penjualan')->with(['gudang', 'driver.user', 'kendaraan']);

        if($request->has(['start_date', 'end_date'])){
            if($request->start_date != '' && $request->end_date != ''){
                $list_pengiriman = $list_pengiriman->whereBetween('tgl_pengiriman', [$request->start_date, $request->end_date]);
            }
        }

        if($request->has('id_gudang')){
            if($request->id_gudang != 'all' && $request->id_gudang != ''){
                $list_pengiriman = $list_pengiriman->where('id_gudang', $request->id_gudang);
            }
        }

        if ($this->user->role == 'driver'){
            $list_pengiriman = $list_pengiriman->where('status','!=' , 'waiting')->where('id_driver', $this->user->id);
        }
        else{
            if($request->has('id_driver')){
                if($request->id_driver != 'all' && $request->id_driver != ''){
                    $list_pengiriman = $list_pengiriman->where('id_driver', $request->id_driver);
                }
            }
        }
        
        if($request->has('id_kendaraan')){
            if($request->id_kendaraan != 'all' && $request->id_kendaraan != ''){
                $list_pengiriman = $list_pengiriman->where('id_kendaraan', $request->id_kendaraan);
            }            
        }

        $list_pengiriman = $list_pengiriman->latest();

        $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
        $list_pengiriman = $perPage == 'all' ? $list_pengiriman->get() : $list_pengiriman->paginate((int)$perPage);

        if ($list_pengiriman) {
            return PengirimanResource::collection($list_pengiriman);
        }
        return response()->json([
            'message' => 'Data Pengiriman tidak ditemukan!'
        ], 404);
    }

    public function show($id)
    {
        $pengiriman = Pengiriman::with(['gudang', 'driver.user', 'kendaraan'])->find($id);

        if ($this->user->role == 'driver' && $pengiriman->id_driver != $this->user->id){
            return response()->json([
                'message' => 'Anda tidak boleh melihat data pengiriman driver lain!'
            ], 400);
        }

        if ($pengiriman) {
            return new PengirimanResource($pengiriman);
        }
        return response()->json([
            'message' => 'Data Pengiriman tidak ditemukan!'
        ], 404);
    }

    public function store(Request $request)
    {
        if ($this->user->role != 'logistik' && $this->user->role != 'admin' && $this->user->role != 'pimpinan'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
            ], 400);
        }

        $this->validate($request, [
            'id_gudang' => 'required|numeric|min:0|max:9999999999',
            'id_driver' => 'required|numeric|min:0|max:9999999999',
            'id_kendaraan' => 'required|numeric|min:0|max:9999999999',
            'tgl_pengiriman' => 'required|date'
            //'keterangan'
        ]);

        $input = $request->all();
        $input['created_by'] = $this->user->id;

        try {
            $pengiriman = Pengiriman::create($input);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        $list_pengiriman = Pengiriman::with(['gudang', 'driver.user', 'kendaraan'])->latest();
        $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
        $list_pengiriman = $perPage == 'all' ? $list_pengiriman->get() : $list_pengiriman->paginate((int)$perPage);
        $new_list_pengiriman = PengirimanResource::collection($list_pengiriman);

        return response()->json([
            'message' => 'Data Pengiriman berhasil disimpan.',
            'data' => $new_list_pengiriman
        ], 201);
    }

    

    public function update(Request $request, $id)
    {
        if ($this->user->role != 'logistik' && $this->user->role != 'admin' && $this->user->role != 'pimpinan'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
            ], 400);
        }

        $pengiriman = Pengiriman::find($id);

        $this->validate($request, [
            'id_gudang' => 'required|numeric|min:0|max:9999999999',
            'id_driver' => 'required|numeric|min:0|max:9999999999',
            'id_kendaraan' => 'required|numeric|min:0|max:9999999999',
            'tgl_pengiriman' => 'required|date'
            //'keterangan'
        ]);

        $input = $request->all();
        $input['updated_by'] = $this->user->id;

        if ($pengiriman) {
            $pengiriman->update($input);

            $list_pengiriman = Pengiriman::with(['gudang', 'driver.user', 'kendaraan'])->latest();
            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            $list_pengiriman = $perPage == 'all' ? $list_pengiriman->get() : $list_pengiriman->paginate((int)$perPage);
            $new_list_pengiriman = PengirimanResource::collection($list_pengiriman);
            
            // return $new_list_pengiriman;
            return response()->json([
                'message' => 'Data Pengiriman telah berhasil diubah.',
                'data' => $new_list_pengiriman
            ], 201);
        }
    
        return response()->json([
            'message' => 'Data Pengiriman tidak ditemukan.'
        ], 404);

    }

    public function destroy($id, Request $request)
    {
        if ($this->user->role != 'logistik' && $this->user->role != 'admin' && $this->user->role != 'pimpinan'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
            ], 400);
        }

        $pengiriman = Pengiriman::find($id);
        
        if($pengiriman) {            
            $penjualan = Penjualan::where('id_pengiriman', $id);
            if($penjualan->count() > 0)
            {
                return response()->json([
                    'message' => 'Anda tidak boleh menghapus data pengiriman yang sudah terisi faktur. Kosongkan faktur terlebih dahulu sebelum menghapus data pengiriman!'
                ], 400);
            }

            $data = ['deleted_by' => $this->user->id];
            $pengiriman->update($data);
            $pengiriman->delete();

            $list_pengiriman = Pengiriman::with(['gudang', 'driver.user', 'kendaraan'])->latest();
            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            $list_pengiriman = $perPage == 'all' ? $list_pengiriman->get() : $list_pengiriman->paginate((int)$perPage);
            $new_list_pengiriman = PengirimanResource::collection($list_pengiriman);

            return response()->json([
                'message' => 'Data Pengiriman berhasil dihapus.',
                'data' => $new_list_pengiriman
            ], 200);
        }

        return response()->json([
            'message' => 'Data Pengiriman tidak ditemukan!'
        ], 404);
    }

    public function restore($id)
    {
        if ($this->user->role != 'admin' && $this->user->role != 'pimpinan'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
            ], 400);
        }

        $pengiriman = Pengiriman::withTrashed()->find($id);
        
        if($pengiriman) {
            $data = ['deleted_by' => null];
            $pengiriman->update($data);
            $pengiriman->restore();

            return response()->json([
                'message' => 'Data Pengiriman berhasil dikembalikan.'
            ], 200);
        }

        return response()->json([
            'message' => 'Data Pengiriman tidak ditemukan!'
        ], 404);
    }

    // get http://localhost:8000/gudang/list_gudang/baik
    // get http://localhost:8000/driver
    // get http://localhost:8000/kendaraan/list/delivery
    // get list data penjualan yang status approved && id_pengiriman = null order by latest
    // get list data penjualan yang status = approved/loaded/delivered && id_pengiriman = isi order by latest
    // nambahin data penjualan ke pengiriman (ubah id_pengriman @ tb penjualan)
    // hapus data penjualan di pengiriman (ubah id_pengriman jadi null @ tb penjualan)
    // SETELAH SPLIT PENGIRIMAN, LOGISTIK APPROVE
    // KEPALA GUDANG LOAD BARANG
    // DRIVER MENGIRIMKAN BARANG KE TOKO

    // Logistik
    // CRUD data pengiriman
    // split faktur (add id_pengiriman @ tb_penjualan)
    // CRUD pengeluaran barang & detailnya
    // Approve pengiriman

    // Kepala gudang (PENGELUARAN BARANG)
    // get data pengeluaran by id + list detail penjualan (rekap barang apa saja yang harus dimasukkan ke mobil)
    // update stock, ubah status penjualan && pengiriman (pengeluaran barang) dari approved menjadi loaded

    // Driver
    // get riwayat pengiriman (get tanggal, get list)
    // get data pengiriman, detail penjualan (status loaded/delivered) + link ke halaman faktur
    // ubah status penjualan menjadi dari loaded menjadi delivered

    // get http://localhost:8000/pengiriman/list/penjualan_belum/10000002 (id_pengiriman)
    public function get_list_penjualan_belum($id_pengiriman)
    {
        $id_gudang = Pengiriman::find($id_pengiriman)->id_gudang;
        $penjualan = Penjualan::with(['toko','salesman.tim.depo'])->where('status', 'approved')->whereNull('id_pengiriman')->oldest()->get()->where('salesman.tim.depo.id_gudang', $id_gudang);
        
        if ($penjualan) {
            return PenjualanPengirimanResource::collection($penjualan);
        }

        return response()->json([
            'message' => 'Faktur Penjualan tidak ditemukan!'
        ], 404);
    }

    // get http://localhost:8000/pengiriman/list/penjualan_sudah/10000001
    public function get_list_penjualan_sudah($id_pengiriman)
    {
        $penjualan = Penjualan::with(['toko','salesman'])->where('id_pengiriman', $id_pengiriman)->whereIn('status', ['approved','loaded', 'delivered'])->oldest()->get();

        if ($penjualan) {
            return response()->json([
                'data' => PenjualanPengirimanResource::collection($penjualan),
                'total_value' => $penjualan->sum('grand_total'),
                'total_qty' => $penjualan->sum('total_qty'),
                'total_pcs' => $penjualan->sum('total_pcs')
            ], 200);
        }
        return response()->json([
            'message' => 'Data Penjualan tidak ditemukan!'
        ], 404);
    }

    // post http://localhost:8000/pengiriman/set/10000001/10000004
    public function set_penjualan($id_pengiriman, $id_penjualan)
    {
        if ($this->user->role != 'logistik' && $this->user->role != 'admin' && $this->user->role != 'pimpinan'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
            ], 400);
        }

        $pengiriman = Pengiriman::find($id_pengiriman);
        if(!$pengiriman){
            return response()->json([
                'message' => 'Data Pengiriman tidak ditemukan!'
            ], 400);
        }
        if($pengiriman->status != 'waiting'){
            return response()->json([
                'message' => 'Anda tidak boleh menambahkan penjualan di pengiriman ini!'
            ], 400);
        }

        $penjualan = Penjualan::find($id_penjualan);
        if($penjualan) {
            if($penjualan->id_pengiriman != ''){
                return response()->json([
                    'message' => 'Faktur penjualan ini telah dimuat di pengiriman lain!'
                ], 400);
            }
            if($penjualan->status != 'approved'){
                return response()->json([
                    'message' => 'Faktur penjualan ini tidak boleh dimasukkan ke daftar pengiriman.'
                ], 400);
            }

            $data = [
                'id_pengiriman' => $id_pengiriman,
                // 'status' => 'loaded',
            ];
            $penjualan->update($data);

            // CREATE data detail_pengeluaran_barang berdasarkan data detail_penjualan
            $detail_penjualan = DetailPenjualan::where('id_penjualan', $id_penjualan)->get();
            foreach($detail_penjualan as $dp){
                $data['id_pengiriman'] = $id_pengiriman;
                $data['id_detail_penjualan'] = $dp->id;
                $data['id_stock'] = $dp->id_stock;
                $data['qty'] = $dp->qty;
                $data['qty_pcs'] = $dp->qty_pcs;
                $data['created_by'] = $this->user->id;
                $detail_pengeluaran_barang = DetailPengeluaranBarang::create($data);
            }

            $list_penjualan = Penjualan::with(['toko','salesman'])->where('id_pengiriman', $id_pengiriman)->whereIn('status', ['approved','loaded', 'delivered'])->latest()->get();
            $new_list_penjualan = PenjualanPengirimanResource::collection($list_penjualan);
            $grand_total = $list_penjualan->sum('grand_total');

            return response()->json([
                'message' => 'Faktur penjualan berhasil ditambahkan ke daftar pengiriman.',
                'data' => $new_list_penjualan,
                'grand_total' => $grand_total
            ], 200);
        }

        return response()->json([
            'message' => 'Data Penjualan tidak ditemukan!'
        ], 404);
    }

    // post http://localhost:8000/pengiriman/unset/10000001/10000004
    public function unset_penjualan($id_pengiriman, $id_penjualan)
    {
        if ($this->user->role != 'logistik' && $this->user->role != 'admin' && $this->user->role != 'pimpinan'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
            ], 400);
        }

        $pengiriman = Pengiriman::find($id_pengiriman);
        if(!$pengiriman){
            return response()->json([
                'message' => 'Data Pengiriman tidak ditemukan!'
            ], 400);
        }
        if($pengiriman->status != 'waiting'){
            return response()->json([
                'message' => 'Anda tidak boleh menambahkan penjualan di pengiriman ini!'
            ], 400);
        }

        $penjualan = Penjualan::find($id_penjualan);
        if($penjualan) {
            if($penjualan->id_pengiriman == ''){
                return response()->json([
                    'message' => 'Faktur penjualan ini telah telah dikeluarkan dari daftar pengiriman!'
                ], 400);
            }
            if($penjualan->status != 'approved'){
                return response()->json([
                    'message' => 'Faktur penjualan ini tidak boleh dikeluarkan ke daftar pengiriman.'
                ], 400);
            }

            $data = [
                'id_pengiriman' => null,
                // 'status' => 'approved'
            ];
            $penjualan->update($data);

            // DESTROY data detail_pengeluaran_barang berdasarkan id_detail_penjualan
            $id_detail_penjualan = DetailPenjualan::where('id_penjualan', $id_penjualan)->pluck('id');
            $detail_pengeluaran_barang = DetailPengeluaranBarang::whereIn('id_detail_penjualan', $id_detail_penjualan)->delete();

            // GET GRAND SUM TOTAL PENJUALAN YANG TER SET
            $list_penjualan = Penjualan::where('id_pengiriman', $id_pengiriman)->whereIn('status', ['approved','loaded', 'delivered'])->latest()->get();                
            $grand_total = $list_penjualan->sum('grand_total');

            // GET LIST PENJUALAN YANG BELUM TER SET
            $id_gudang = Pengiriman::find($id_pengiriman)->id_gudang;
            $list_penjualan_belum = Penjualan::with(['toko','salesman.tim.depo'])->where('status', 'approved')->whereNull('id_pengiriman')->oldest()->get()->where('salesman.tim.depo.id_gudang', $id_gudang);        
            $new_list_penjualan_belum = PenjualanPengirimanResource::collection($list_penjualan_belum);

            return response()->json([
                'message' => 'Faktur penjualan berhasil dihapus dari daftar pengiriman.',
                'data' => $new_list_penjualan_belum,
                'grand_total' => $grand_total
            ], 200);
        }

        return response()->json([
            'message' => 'Data Penjualan tidak ditemukan!'
        ], 404);
    }

    // DRIVER
    // http://localhost:8000/pengiriman/list/tanggal
    public function list_tanggal() {
        $list_pengiriman = Pengiriman::where('status', 'loaded');
        if ($this->user->role == 'driver'){
            $list_pengiriman = $list_pengiriman->where('id_driver', $this->user->id);
        }

        $list_pengiriman = $list_pengiriman->latest()->select('tgl_pengiriman as tanggal', \DB::raw('count(*) as count'))->groupBy('tgl_pengiriman')->get();

        return $list_pengiriman;
    }


}
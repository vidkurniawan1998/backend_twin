<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Pengiriman;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\DetailPengeluaranBarang;
use App\Models\Stock;
use App\Models\Driver;
use App\Models\PosisiStock;
use App\Http\Resources\Pengiriman as PengirimanResource;
use App\Http\Resources\DetailPenjualan as DetailPenjualanResource;
use App\Http\Resources\DetailPengeluaranBarang as DetailPengeluaranBarangResource;
use App\Http\Resources\PenjualanPengiriman as PenjualanPengirimanResource;
use App\Http\Resources\DetailPengeluaranBarang2 as DetailPengeluaranBarang2Resource;
use App\Http\Resources\DetailPenjualanPengiriman as DetailPenjualanPengirimanResource;
use PDF;

class PengeluaranBarangController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    // REV: tambahin fungsi create, update, delete pengeluaran barang (model Pengiriman)
    // C, U, D untuk role logistik
    // Read, Approve loaded kepala gudang
    // read driver

    // REV : JUL 2019
    // UPDATE CRUD menggunakan logic yg baru (eagerload, get data disetiap CRUD)
    // FUNGSI LOAD BARANG ADA DI HALAMAN PENGELUARAN BARANG (OLEH KEPALA GUDANG)
    // FUNGSI APPROVE ADA DI HALAMAN PENGIRIMAN (OLEH LOGISTIK)

    public function index(Request $request)
    {
        //read: admin, pimpinan, accounting, kepala_gudang, logistik, driver
        if ($this->user->role != 'admin' && $this->user->role != 'pimpinan' && $this->user->role != 'accounting' && $this->user->role != 'kepala_gudang' && $this->user->role != 'logistik' && $this->user->role != 'driver'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
            ], 400);
        }

        $list_pengiriman = Pengiriman::with(['gudang', 'driver.user', 'kendaraan']);

        if ($this->user->role == 'driver'){
            $list_pengiriman = $list_pengiriman->whereNot('status', 'waiting')->where('id_driver', $this->user->id)->latest();
        }
        else {
            $list_pengiriman = $list_pengiriman->latest();
        }

        $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
        $list_pengiriman = $perPage == 'all' ? $list_pengiriman->get() : $list_pengiriman->paginate((int)$perPage);

        if ($list_pengiriman) {
            return PengirimanResource::collection($list_pengiriman);
        }
        return response()->json([
            'message' => 'Data Pengeluaran Barang tidak ditemukan!'
        ], 404);
    }

    public function show($id)
    {
        //read: admin, pimpinan, kepala_gudang, logistik, driver, accounting
        if ($this->user->role != 'admin' && $this->user->role != 'pimpinan' && $this->user->role != 'accounting' && $this->user->role != 'kepala_gudang' && $this->user->role != 'logistik' && $this->user->role != 'driver'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
            ], 400);
        }

        $pengiriman = Pengiriman::with(['gudang', 'driver.user', 'kendaraan'])->find($id);

        if ($pengiriman) {
            return new PengirimanResource($pengiriman);
        }
        return response()->json([
            'message' => 'Data Pengeluaran Barang tidak ditemukan!'
        ], 404);
    }

    public function store(Request $request)
    {
        if ($this->user->role != 'admin' && $this->user->role != 'pimpinan' && $this->user->role != 'logistik'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
            ], 400);
        }

        $this->validate($request, [
            'tgl_pengiriman' => 'required|date',
            'id_kendaraan' => 'required|numeric|min:0|max:9999999999',
            'id_driver' => 'required|numeric|min:0|max:9999999999',
            'id_gudang' => 'required|numeric|min:0|max:9999999999',
        ]);

        $input = $request->all();
        // $input['id_gudang'] = Driver::find($request->id_driver)->tim->depo->id_gudang;
        $input['created_by'] = $this->user->id;
            
        try {
            $pengeluaran_barang = Pengiriman::create($input);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        $list_pengiriman = Pengiriman::with(['gudang', 'driver.user', 'kendaraan'])->latest();
        $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
        $list_pengiriman = $perPage == 'all' ? $list_pengiriman->get() : $list_pengiriman->paginate((int)$perPage);
        $new_list_pengiriman = PengirimanResource::collection($list_pengiriman);

        return response()->json([
            'message' => 'Data Pengeluaran Barang berhasil disimpan.',
            'data' => $new_list_pengiriman
        ], 201);
    }

    public function update(Request $request, $id)
    {
        if ($this->user->role != 'admin' && $this->user->role != 'pimpinan' && $this->user->role != 'logistik'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
            ], 400);
        }

        $pengeluaran_barang = Pengiriman::find($id);

        $this->validate($request, [
            'tgl_pengiriman' => 'required|date',
            'id_kendaraan' => 'required|numeric|min:0|max:9999999999',
            'id_driver' => 'required|numeric|min:0|max:9999999999',
            'id_gudang' => 'required|numeric|min:0|max:9999999999',
        ]);

        $input = $request->all();
        // $input['id_gudang'] = Driver::find($request->id_driver)->tim->depo->id_gudang;
        $input['updated_by'] = $this->user->id;

        if ($pengeluaran_barang) {
            $pengeluaran_barang->update($input);
            
            $list_pengiriman = Pengiriman::with(['gudang', 'driver.user', 'kendaraan'])->latest();;
            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            $list_pengiriman = $perPage == 'all' ? $list_pengiriman->get() : $list_pengiriman->paginate((int)$perPage);
            $new_list_pengiriman = PengirimanResource::collection($list_pengiriman);
    
            return response()->json([
                'message' => 'Data Pengeluaran Barang telah berhasil diubah.',
                'data' => $new_list_pengiriman
            ], 201);
        }

        return response()->json([
            'message' => 'Data Pengeluaran Barang tidak ditemukan.'
        ], 404);
    }

    public function destroy($id, Request $request)
    {
        if ($this->user->role != 'admin' && $this->user->role != 'pimpinan' && $this->user->role != 'logistik'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
            ], 400);
        }

        $pengeluaran_barang = Pengiriman::find($id);

        if($pengeluaran_barang->detail_pengeluaran_barang->count() != 0) {
            return response()->json([
                'message' => 'Kosongkan detail pengeluaran barang terlebih dahulu!'
            ], 422);
        }
        
        if($pengeluaran_barang) {
            $data = ['deleted_by' => $this->user->id];
            $pengeluaran_barang->update($data);
            $pengeluaran_barang->delete();

            $list_pengiriman = Pengiriman::with(['gudang', 'driver.user', 'kendaraan'])->latest();
            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            $list_pengiriman = $perPage == 'all' ? $list_pengiriman->get() : $list_pengiriman->paginate((int)$perPage);
            $new_list_pengiriman = PengirimanResource::collection($list_pengiriman);

            return response()->json([
                'message' => 'Data Pengeluaran Barang berhasil dihapus.',
                'data' => $new_list_pengiriman
            ], 200);
        }

        return response()->json([
            'message' => 'Data Pengeluaran Barang tidak ditemukan!'
        ], 404);
    }

    public function restore($id, Request $request)
    {
        if ($this->user->role != 'admin' && $this->user->role != 'pimpinan'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
            ], 400);
        }

        $pengeluaran_barang = Pengiriman::withTrashed()->find($id);
        
        if($pengeluaran_barang) {
            $data = ['deleted_by' => null];
            $pengeluaran_barang->update($data);
            $pengeluaran_barang->restore();

            $list_pengiriman = Pengiriman::with(['gudang', 'driver.user', 'kendaraan'])->latest();
            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            $list_pengiriman = $perPage == 'all' ? $list_pengiriman->get() : $list_pengiriman->paginate((int)$perPage);
            $new_list_pengiriman = PengirimanResource::collection($list_pengiriman);

            return response()->json([
                'message' => 'Data Pengeluaran Barang berhasil dikembalikan.',
                'data' => $new_list_pengiriman
            ], 200);
        }

        return response()->json([
            'message' => 'Data Pengeluaran Barang tidak ditemukan!'
        ], 404);
    }

    // http://localhost:8000/pengeluaran_barang/list_invoice/10000001 untuk driver
    public function list_invoice($id_pengiriman) // get list invoice yang ada dalam pengiriman
    {
        $list_invoice = Penjualan::with(['toko', 'salesman.user', 'salesman.tim'])->where('id_pengiriman', $id_pengiriman); //->whereIn('status', ['loaded', 'delivered'])
        $list_invoice = $list_invoice->get();
        return PenjualanPengirimanResource::collection($list_invoice);
    }

    // http://localhost:8000/pengeluaran_barang/list_barang/10000001 untuk kepala gudang
    public function list_barang($id_pengiriman) // get rekap barang dari semua invoice
    {
        // if ($this->user->role != 'admin' && $this->user->role != 'pimpinan' && $this->user->role != 'accounting' && $this->user->role != 'kepala_gudang' && $this->user->role != 'logistik' && $this->user->role != 'driver'){
        //     return response()->json([
        //         'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
        //     ], 400);
        // }

        // $list_detail_pengeluaran_barang = DetailPengeluaranBarang::where('id_pengiriman', $id)->get()->sortBy('kode_barang');
        // if ($list_detail_pengeluaran_barang) {
        //     return DetailPengeluaranBarangResource::collection($list_detail_pengeluaran_barang);
        // }

        $list_detail_pengeluaran_barang = DetailPengeluaranBarang::with('stock.barang')->where('id_pengiriman', $id_pengiriman)->select('id_stock', \DB::raw('sum(qty) as qty, sum(qty_pcs) as qty_pcs'))->groupBy('id_stock')->get()->sortBy('kode_barang');
        // return $list_detail_pengeluaran_barang;
        return DetailPengeluaranBarang2Resource::collection($list_detail_pengeluaran_barang);
    }

    public function detail_penjualan($id_penjualan)
    {
        $list_detail_penjualan = DetailPenjualan::where('id_penjualan', $id_penjualan)->get();
        if ($list_detail_penjualan) {
            // return $list_detail_penjualan;
            // return $list_detail_penjualan->sortBy('kode_barang');
            return DetailPenjualanPengirimanResource::collection($list_detail_penjualan->sortBy('kode_barang'));
        }
        return response()->json([
            'message' => 'Data Detail Penjualan tidak ditemukan!'
        ], 404);
    }

    public function approve(Request $request, $id)
    {
        if ($this->user->role != 'admin' && $this->user->role != 'pimpinan' && $this->user->role != 'logistik'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
            ], 400);
        }

        $pengeluaran_barang = Pengiriman::findOrFail($id);

        if($pengeluaran_barang->status != 'waiting') {
            return response()->json([
                'message' => 'Data Pengeluaran Barang telah disetujui.'
            ], 422);
        }

        if(DetailPengeluaranBarang::where('id_pengiriman', $id)->count() <= 0) {
            return response()->json([
                'message' => 'Data Pengeluaran Barang masih kosong, isi data barang terlebih dahulu.'
            ], 422);
        }
        
        $pengeluaran_barang->status = 'approved';
        $pengeluaran_barang->save();

        
        // $list_pengiriman = Pengiriman::with(['gudang', 'driver.user', 'kendaraan'])->find($id);
        // $list_pengiriman = Pengiriman::with(['gudang', 'driver.user', 'kendaraan'])->latest();
        // $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
        // $list_pengiriman = $perPage == 'all' ? $list_pengiriman->get() : $list_pengiriman->paginate((int)$perPage);
        // $new_list_pengiriman = PengirimanResource::collection($list_pengiriman);
        // $new_list_pengiriman = new PengirimanResource($list_pengiriman);

        return response()->json([
            'message' => 'Data pengeluaran barang telah disetujui.',
            // 'data' => $new_list_pengiriman
        ], 200);
    }

    public function load_barang($id_pengiriman){
        if ($this->user->role != 'admin' && $this->user->role != 'pimpinan' && $this->user->role != 'kepala_gudang'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
            ], 400);
        }

        $pengiriman = Pengiriman::find($id_pengiriman);

        if($pengiriman->status == 'waiting'){
            return response()->json([
                'message' => 'Pengeluaran barang belum disetujui.'
            ], 422);
        }
        elseif($pengiriman->status == 'loaded'){
            return response()->json([
                'message' => 'Barang telah dimuat ke mobil.'
            ], 422);
        }

        $detail_pengeluaran_barang = DetailPengeluaranBarang::where('id_pengiriman', $id_pengiriman)->get();

        //cek stock
        foreach($detail_pengeluaran_barang as $dpb) {
            $stock = Stock::find($dpb->id_stock);
            $stock_akhir = $stock->qty - $dpb->qty;
            //jika stock tidak cukup batalkan
            if ($stock_akhir < 0) {
                return response()->json([
                    'message' => 'Stock barang di gudang tidak cukup, mohon periksa data pengeluaran barang kembali!'
                ], 400);
            }
        }

        //kurangi stock gudang
        foreach($detail_pengeluaran_barang as $dpb) {
            $stock = Stock::find($dpb->id_stock);
            $stock->decrement('qty', $dpb->qty);
            $stock->decrement('qty_pcs', $dpb->qty_pcs);
            if($stock->qty_pcs < 0){ // jika qty_pcs min, pecah 1 dus
                $stock->decrement('qty',1);
                $stock->increment('qty_pcs', $stock->barang->isi);
            }
            // $stock->qty = $stock->qty - $dpb->qty;
            // $stock->qty_pcs = $stock->qty_pcs - $dpb->qty_pcs;
            // $stock->save();

            // REV: CATAT DI TABEL POSISI STOCK (STOCK AKHIR DECREMENT, PENJUALAN INCREMENT)
            $today = \Carbon\Carbon::today()->toDateString();
            $posisi_stock = PosisiStock::where('tanggal', $today)->where('id_stock', $dpb->id_stock)->first();
            $posisi_stock->increment('penjualan_qty', $dpb->qty);
            $posisi_stock->increment('penjualan_pcs', $dpb->qty_pcs);
            $posisi_stock->decrement('saldo_akhir_qty', $dpb->qty);
            $posisi_stock->decrement('saldo_akhir_pcs', $dpb->qty_pcs);
            if($posisi_stock->saldo_akhir_pcs < 0){ // jika qty_pcs min, pecah 1 dus
                $posisi_stock->decrement('saldo_akhir_qty',1);
                $posisi_stock->increment('saldo_akhir_pcs', $stock->barang->isi);
            }
        }

        // set status pengiriman dari approved menjadi loaded
        $pengiriman->update(['status' => 'loaded', 'updated_by' => $this->user->id]);

        // set status penjualan dari approved menjadi loaded
        $id_penjualan = Penjualan::where('id_pengiriman', $id_pengiriman)->where('status', 'approved')->update(['status' => 'loaded', 'updated_by' => $this->user->id]);
        // foreach($id_penjualan as $ip){
        //     Penjualan::find($ip)->update(['status' => 'loaded', 'updated_by' => $this->user->id]);
        // }

        return response()->json([
            'message' => 'Loading barang berhasil!'
        ], 200);
    }

    // FUNGSI LOAD BARANG LAMA
    // public function load_barang($id_pengiriman){
    //     //user: kepala gudang
    //     //get id penjualan
    //     //get in detail penjualan
    //     //cek stock gudang, kalo gk cukup kasi warning
    //     //kurangi stock gudang berdasarkan detail penjualan
    //     //set seluruh status data penjualan dari approved menjadi loaded.

    //     $id_penjualan = Penjualan::where('id_pengiriman', $id_pengiriman)->where('status', 'approved')->pluck('id');
    //     $detail_penjualan = DetailPenjualan::whereIn('id_penjualan', $id_penjualan)->get();

    //     //cek stock
    //     foreach($detail_penjualan as $dpj) {
    //         $stock = Stock::find($dpj->id_stock);
    //         $stock_akhir = $stock->qty - $dpj->qty;

    //         //jika stock tidak cukup batalkan
    //         if ($stock_akhir < 0) {
    //             return response()->json([
    //                 'message' => 'Stock barang di gudang tidak cukup, Mohon periksa data penjualan kembali!'
    //             ], 400);
    //         }
    //     }

    //     //kurangi stock gudang
    //     foreach($detail_penjualan as $dpj) {
    //         $stock = Stock::find($dpj->id_stock);
    //         $stock->qty = $stock->qty - $dpj->qty;
    //         $stock->qty_pcs = $stock->qty_pcs - $dpj->qty_pcs;
    //         $stock->save();
    //     }
        
    //     //set status penjualan dari approved menjadi loaded
    //     foreach($id_penjualan as $ip){
    //         Penjualan::where('id', $ip)->where('status', 'approved')->update(['status' => 'loaded', 'updated_by' => $this->user->id]);
    //     }

    //     return response()->json([
    //         'message' => 'Loading barang berhasil!'
    //     ], 200);

    // }



    // public function generatePDF($id){ //list invoice aja, tanpa rekap barang

    //     if ($this->user->role != 'admin' && $this->user->role != 'pimpinan' && $this->user->role != 'accounting'){
    //         return response()->json([
    //             'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
    //         ], 400);
    //     }

    //     $pengiriman = Pengiriman::with('gudang', 'kendaraan', 'driver.user')->find($id);
    //     $now = \Carbon\Carbon::now();
    //     $list_penjualan = Penjualan::with(['toko','salesman', 'detail_penjualan'])->where('id_pengiriman', $id)->whereIn('status', ['approved','loaded', 'delivered'])->oldest()->get();
                
    //     $pdf = PDF::loadView('pdf.pengiriman_pdf', compact('pengiriman', 'now', 'list_penjualan'))->setPaper('letter', 'portrait');
    //     return $pdf->stream('pengiriman-' . $id . '.pdf');
    // }

    public function generatePDF($id){

        if ($this->user->role != 'admin' && $this->user->role != 'pimpinan' && $this->user->role != 'accounting'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
            ], 400);
        }

        $pengiriman = Pengiriman::with('gudang', 'kendaraan', 'driver.user')->find($id);
        $now = \Carbon\Carbon::now();
        $generated_by = ucwords($this->user->name);

        $list_penjualan = Penjualan::with(['toko','salesman', 'detail_penjualan'])->where('id_pengiriman', $id)->whereIn('status', ['approved','loaded', 'delivered'])->oldest()->get();
        
        $list_detail_pengeluaran_barang = \DB::table('detail_pengeluaran_barang')
                                ->where('id_pengiriman', $id)
                                ->join('stock', 'stock.id', '=', 'detail_pengeluaran_barang.id_stock')
                                ->join('barang', 'barang.id', '=', 'stock.id_barang')
                                ->select('detail_pengeluaran_barang.id_stock', 'barang.kode_barang', 'barang.nama_barang', \DB::raw('sum(detail_pengeluaran_barang.qty) as total_qty'), \DB::raw('sum(detail_pengeluaran_barang.qty_pcs) as total_pcs'))
                                ->groupBy('detail_pengeluaran_barang.id_stock')
                                ->get()->sortBy('barang.kode_barang');

        $pdf = PDF::loadView('pdf.pengiriman_pdf', compact('pengiriman', 'now', 'generated_by', 'list_penjualan', 'list_detail_pengeluaran_barang'))->setPaper('letter', 'portrait');
        return $pdf->stream('pengiriman-' . $id . '.pdf');
    }


}
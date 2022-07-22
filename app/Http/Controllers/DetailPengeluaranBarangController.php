<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\DetailPengeluaranBarang;
use App\Models\PengeluaranBarang;
use App\Models\Pengiriman;
use App\Models\Stock;
use App\Http\Resources\DetailPengeluaranBarang as DetailPengeluaranBarangResource;
use App\Http\Resources\DetailPengeluaranBarang2 as DetailPengeluaranBarang2Resource;
use App\Http\Resources\Stock as StockResource;

class DetailPengeluaranBarangController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index()
    {
        if ($this->user->role != 'admin' && $this->user->role != 'pimpinan' && $this->user->role != 'accounting' && $this->user->role != 'kepala_gudang' && $this->user->role != 'logistik'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
            ], 400);
        }

        $list_detail_pengeluaran_barang = DetailPengeluaranBarang::latest()->get();

        if ($list_detail_pengeluaran_barang) {
            return DetailPengeluaranBarangResource::collection($list_detail_pengeluaran_barang);
        }
        return response()->json([
            'message' => 'Data Detail Pengeluaran Barang tidak ditemukan!'
        ], 404);
    }

    public function show($id)
    {
        if ($this->user->role != 'admin' && $this->user->role != 'pimpinan' && $this->user->role != 'accounting' && $this->user->role != 'kepala_gudang' && $this->user->role != 'logistik'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
            ], 400);
        }
        
        $detail_pengeluaran_barang = DetailPengeluaranBarang::find($id);

        if ($detail_pengeluaran_barang) {
            return new DetailPengeluaranBarangResource($detail_pengeluaran_barang);
        }
        return response()->json([
            'message' => 'Data Detail Pengeluaran Barang tidak ditemukan!'
        ], 404);
    }

    public function detail($id) // get rekap barang dari semua invoice
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

        // $list_detail_pengeluaran_barang = DetailPengeluaranBarang::where('id_pengiriman', $id)->get()->groupBy('id_stock');//->sortBy('kode_barang');
        $list_detail_pengeluaran_barang = DetailPengeluaranBarang::where('id_pengiriman', $id)->select('id_stock', \DB::raw('sum(qty) as qty, sum(qty_pcs) as qty_pcs'))->groupBy('id_stock')->get()->sortBy('kode_barang');
        return DetailPengeluaranBarang2Resource::collection($list_detail_pengeluaran_barang);
    }

    public function stock_barang(Request $request) // parameter: id_pengiriman
    {
        $pengiriman = Pengiriman::find($request->id_pengiriman);
        if(!$pengiriman){
            return response()->json([
                'message' => 'Data Pengiriman Barang tidak ditemukan'
            ], 404);
        }

        $stock = Stock::where('id_gudang', $pengiriman->id_gudang)->get()->sortBy('nama_barang');

        if ($stock) {
            return StockResource::collection($stock);
        }
        return response()->json([
            'message' => 'Data Stock Barang tidak ditemukan!'
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
            'id_pengiriman' => 'required|numeric|min:0|max:9999999999', // dpt dr pengeluaran_barang@show
            'id_stock' => 'required|numeric|min:0|max:9999999999',
            'qty' => 'required|numeric|min:0|max:9999999999',
            'qty_pcs' => 'required|numeric|min:0|max:9999999999'
        ]);

        $pengeluaran_barang = Pengiriman::find($request->id_pengiriman);

        if($pengeluaran_barang->status == 'approved') {
            return response()->json([
                'message' => 'Anda tidak boleh menambahkan data barang pada Pengeluaran Barang yang telah disetujui.'
            ], 422);
        }

        $input = $request->all();
        $input['created_by'] = $this->user->id;

        try {
            $detail_pengeluaran_barang = DetailPengeluaranBarang::create($input);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        $list_detail_pengeluaran_barang = DetailPengeluaranBarang::where('id_pengiriman', $request->id_pengiriman)->get()->sortBy('kode_barang');
        $new_detail_pengeluaran_barang =  DetailPengeluaranBarangResource::collection($list_detail_pengeluaran_barang);

        return response()->json([
            'message' => 'Data Detail Pengeluaran Barang berhasil disimpan.',
            'data' => $new_detail_pengeluaran_barang
        ], 201);
    }

    public function update(Request $request, $id)
    {
        if ($this->user->role != 'admin' && $this->user->role != 'pimpinan' && $this->user->role != 'logistik'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
            ], 400);
        }

        $detail_pengeluaran_barang = DetailPengeluaranBarang::find($id);

        if ($detail_pengeluaran_barang->id_detail_penjualan != null){
            return response()->json([
                'message' => 'Anda tidak boleh mengubah data ini!'
            ], 400);
        }

        $pengeluaran_barang = Pengiriman::find($detail_pengeluaran_barang->id_pengiriman);

        if($pengeluaran_barang->status == 'approved') {
            return response()->json([
                'message' => 'Anda tidak boleh mengubah data barang pada Pengeluaran Barang yang telah disetujui.'
            ], 422);
        }

        $this->validate($request, [
            'id_pengiriman' => 'required|numeric|min:0|max:9999999999', // dpt dr pengeluaran_barang@show
            'id_stock' => 'required|numeric|min:0|max:9999999999',
            'qty' => 'required|numeric|min:0|max:9999999999',
            'qty_pcs' => 'required|numeric|min:0|max:9999999999'
        ]);

        $input = $request->all();
        $input['updated_by'] = $this->user->id;

        if ($detail_pengeluaran_barang) {
            $detail_pengeluaran_barang->update($input);

            $list_detail_pengeluaran_barang = DetailPengeluaranBarang::where('id_pengiriman', $request->id_pengiriman)->get()->sortBy('kode_barang');
            $new_detail_pengeluaran_barang =  DetailPengeluaranBarangResource::collection($list_detail_pengeluaran_barang);
    
            return response()->json([
                'message' => 'Data Detail Pengeluaran Barang telah berhasil diubah.',
                'data' => $new_detail_pengeluaran_barang
            ], 201);
        }
    
        return response()->json([
            'message' => 'Data Detail Mutasi Barang tidak ditemukan.'
        ], 404);
    }

    public function destroy($id)
    {
        if ($this->user->role != 'admin' && $this->user->role != 'pimpinan' && $this->user->role != 'logistik'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
            ], 400);
        }

        $detail_pengeluaran_barang = DetailPengeluaranBarang::find($id);

        if(!$detail_pengeluaran_barang){
            return response()->json([
                'message' => 'Data Detail Pengeluaran Barang tidak ditemukan!'
            ], 404);
        }

        if ($detail_pengeluaran_barang->id_detail_penjualan != null){
            return response()->json([
                'message' => 'Data pengeluaran barang ini berasal dari faktur penjualan. Anda tidak boleh menghapus data ini!'
            ], 400);
        }

        $pengeluaran_barang = Pengiriman::find($detail_pengeluaran_barang->id_pengiriman);

        if($pengeluaran_barang->status == 'approved') {
            return response()->json([
                'message' => 'Anda tidak boleh menghapus data barang pada Pengeluaran Barang yang telah disetujui.'
            ], 422);
        }
        
        if($detail_pengeluaran_barang) {
            $detail_pengeluaran_barang->delete();

            $list_detail_pengeluaran_barang = DetailPengeluaranBarang::where('id_pengiriman', $detail_pengeluaran_barang->id_pengiriman)->get()->sortBy('kode_barang');
            $new_detail_pengeluaran_barang =  DetailPengeluaranBarangResource::collection($list_detail_pengeluaran_barang);

            return response()->json([
                'message' => 'Data Detail Pengeluaran Barang berhasil dihapus.',
                'data' => $new_detail_pengeluaran_barang
            ], 200);

        }
        
    }

    public function generatePDF($id) // generate rekap barang dari semua invoice dalam suatu pengiriman
    {
        if ($this->user->role != 'admin' && $this->user->role != 'pimpinan' && $this->user->role != 'accounting'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
            ], 400);
        }

        $pengiriman = Pengiriman::with('gudang', 'kendaraan', 'driver.user')->find($id);
        $now = \Carbon\Carbon::now();

        $list_detail_pengeluaran_barang = \DB::table('detail_pengeluaran_barang')
                                ->where('id_pengiriman', $id)
                                ->join('stock', 'stock.id', '=', 'detail_pengeluaran_barang.id_stock')
                                ->join('barang', 'barang.id', '=', 'stock.id_barang')
                                ->select('detail_pengeluaran_barang.id_stock', 'barang.kode_barang', 'barang.nama_barang', \DB::raw('sum(detail_pengeluaran_barang.qty) as total_qty'), \DB::raw('sum(detail_pengeluaran_barang.qty_pcs) as total_pcs'))
                                ->groupBy('detail_pengeluaran_barang.id_stock')
                                ->get()->sortBy('barang.kode_barang');

        $pdf = \PDF::loadView('pdf.rekap_pengiriman_pdf', compact('pengiriman', 'now', 'list_detail_pengeluaran_barang'))->setPaper('letter', 'portrait');
        return $pdf->stream('rekap_pengiriman-' . $id . '.pdf');
    }


}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Adjustment;
use App\Models\DetailAdjustment;
use App\Models\Stock;
use App\Models\HargaBarang;
use App\Http\Resources\DetailAdjustment as DetailAdjustmentResource;

class DetailAdjustmentController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index() {
        if ($this->user->can('Menu Adjustment Barang')):
            $list_detail_adjustment = DetailAdjustment::latest()->paginate(5);
    
            if ($list_detail_adjustment) {
                return DetailAdjustmentResource::collection($list_detail_adjustment);
            }
            return response()->json([
                'message' => 'Data Adjustment tidak ditemukan!'
            ], 404);
        else: 
            return $this->Unauthorized();
        endif;
    }

    public function show($id) {
        if ($this->user->can('Edit Adjustment Barang')):
            $detail_adjustment = DetailAdjustment::find($id);
    
            if ($detail_adjustment) {
                return new DetailAdjustmentResource($detail_adjustment);
            }
            return response()->json([
                'message' => 'Data Detail Adjustment tidak ditemukan!'
            ], 404);
        else: 
            return $this->Unauthorized();
        endif;
    }

    public function detail($id) {
        if ($this->user->can('Edit Adjustment Barang')):
            $list_detail_adjustment = DetailAdjustment::where('id_adjustment', $id)->get()->sortBy('kode_barang');
            if ($list_detail_adjustment) {
                return DetailAdjustmentResource::collection($list_detail_adjustment);
            }
            return response()->json([
                'message' => 'Data Detail Adjustment tidak ditemukan!'
            ], 404);
        else: 
            return $this->Unauthorized();
        endif;
    }

    public function store(Request $request) {
        if ($this->user->can('Tambah Adjustment Barang')):
            $adjustment = Adjustment::find($request->id_adjustment);
    
            if($adjustment->status != 'waiting') {
                return response()->json([
                    'message' => 'Anda tidak boleh menambahkan data barang pada Adjustment yang telah disetujui.'
                ], 422);
            }
    
            $this->validate($request, [
                'id_adjustment' => 'required|numeric|min:0|max:9999999999',
                'id_stock' => 'required|numeric|min:0|max:9999999999',
                // 'id_harga' => 'numeric|min:0|max:9999999999',
                'qty_adj' => 'required|numeric',
                'pcs_adj' => 'required|numeric'
            ]);
    
            $input = $request->all();
            $input['created_by'] = $this->user->id;
    
            // $input['id_harga'] = 0;
            if(!$request->has('id_harga')) {
                $id_barang = Stock::find($request->id_stock)->id_barang;
                $harga = HargaBarang::where('id_barang', $id_barang)->where('tipe_harga', 'dbp')->latest()->first(); // default DBP
                if($harga) {
                    $input['id_harga'] = $harga->id;
                }
                else{
                    $input['id_harga'] = 0;
                }
            }
    
            try {
                $detail_adjustment = DetailAdjustment::create($input);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
    
            $list_detail_adjustment = DetailAdjustment::where('id_adjustment', $request->id_adjustment)->get()->sortBy('kode_barang');
    
            return response()->json([
                'message' => 'Data Detail Adjustment berhasil disimpan.',
                'data' => DetailAdjustmentResource::collection($list_detail_adjustment)
            ], 201);
        else: 
            return $this->Unauthorized();
        endif;
    }

    public function update(Request $request, $id) {
        if ($this->user->can('Update Adjustment Barang')):
            $detail_adjustment = DetailAdjustment::find($id);
            $adjustment = Adjustment::find($detail_adjustment->id_adjustment);
    
            if($adjustment->status != 'waiting') {
                return response()->json([
                    'message' => 'Anda tidak boleh mengubah data barang pada Adjustment yang telah disetujui.'
                ], 422);
            }
    
            $this->validate($request, [
                'id_adjustment' => 'required|numeric|min:0|max:9999999999',
                'id_stock' => 'required|numeric|min:0|max:9999999999',
                'qty_adj' => 'required|numeric',
                'pcs_adj' => 'required|numeric'
            ]);
    
            $input = $request->all();
            $input['id_harga'] = $detail_adjustment->id_harga;
            $input['updated_by'] = $this->user->id;
    
            if ($detail_adjustment) {
                $detail_adjustment->update($input);
    
                $list_detail_adjustment = DetailAdjustment::where('id_adjustment', $detail_adjustment->id_adjustment)->get()->sortBy('kode_barang');
        
                return response()->json([
                    'message' => 'Data Detail Adjustment telah berhasil diubah.',
                    'data' => DetailAdjustmentResource::collection($list_detail_adjustment)
                ], 201);
            }
        
            return response()->json([
                'message' => 'Data Detail Adjustment tidak ditemukan.'
            ], 404);
        else: 
            return $this->Unauthorized();
        endif;
    }

    public function destroy($id, Request $request) {
        if ($this->user->can('Hapus Adjustment Barang')):
            $detail_adjustment = DetailAdjustment::find($id);
            $adjustment = Adjustment::find($detail_adjustment->id_adjustment);
    
            if($adjustment->status != 'waiting') {
                return response()->json([
                    'message' => 'Anda tidak boleh menghapus data barang pada Adjustment yang telah disetujui.'
                ], 422);
            }
    
            if($detail_adjustment) {
                // $data = ['deleted_by' => $this->user->id];
                // $detail_adjustment->update($data);
                $detail_adjustment->delete();
    
                $list_detail_adjustment = DetailAdjustment::where('id_adjustment', $detail_adjustment->id_adjustment)->get()->sortBy('kode_barang');
    
                return response()->json([
                    'message' => 'Data Detail Penerimaan Barang berhasil dihapus.',
                    'data' => DetailAdjustmentResource::collection($list_detail_adjustment)
                ], 200);
            }
    
            return response()->json([
                'message' => 'Data Detail Adjustment tidak ditemukan!'
            ], 404);
        else: 
            return $this->Unauthorized();
        endif;
    }

    // public function restore($id)
    // {
    //     if ($this->user->role != 'admin' && $this->user->role != 'pimpinan'){
    //         return response()->json([
    //             'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
    //         ], 400);
    //     }
    //     $detail_penerimaan_barang = DetailPenerimaanBarang::withTrashed()->find($id);
        
    //     if($detail_penerimaan_barang) {
    //         $data = ['deleted_by' => null];
    //         $detail_penerimaan_barang->update($data);

    //         $detail_penerimaan_barang->restore();

    //         return response()->json([
    //             'message' => 'Data Detail Penerimaan Barang berhasil dikembalikan.'
    //         ], 200);
    //     }

    //     return response()->json([
    //         'message' => 'Data Detail Penerimaan Barang tidak ditemukan!'
    //     ], 404);
    // }

    // public function generatePDF($id){
    //     $penerimaan = \App\Models\PenerimaanBarang::join('gudang', 'gudang.id', '=', 'penerimaan_barang.id_gudang')
    //         ->join('users', 'users.id', '=', 'penerimaan_barang.created_by')
    //         ->join('principal', 'principal.id', '=', 'penerimaan_barang.id_principal')
    //         ->where('penerimaan_barang.id', $id)
    //         ->select('users.name as create_by_name', 'users.*', 'gudang.*', 'penerimaan_barang.*', 'principal.*', 'principal.id as principal_id')
    //         ->first();
    //     // dd($penerimaan);
    //     $rr_penerimaan = PenerimaanBarang::findOrFail($id);
    //     $r_penerimaan = new PenerimaanBarangResource($rr_penerimaan);
    //     $detail = \App\Models\DetailPenerimaanBarang::where('id', $id)->get();
    //     $r_detail = DetailPenerimaanBarangResource::collection($detail);
    //     $user = $this->jwt->user();
    //     // return $r_detail;
    //     // return $r_penerimaan;
    //     $pdf = PDF::loadView('pdf.detail-penerimaan', compact('penerimaan', 'r_penerimaan', 'detail', 'r_detail', 'user'));
    //     // return $pdf->download('detail-penerimaan-'.$id.'-.pdf');
    //     return $pdf->stream('penerimaan_barang-' . $id . '-.pdf');
    // }
}

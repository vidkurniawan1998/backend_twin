<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\DetailMutasiBarang;
use App\Models\MutasiBarang;
use App\Http\Resources\MutasiBarang as MutasiBarangResource;
use App\Http\Resources\DetailMutasiBarang as DetailMutasiBarangResource;
use Barryvdh\DomPDF\Facade\Pdf;

class DetailMutasiBarangController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index()
    {
        if ($this->user->can('Menu Mutasi Barang')) :
            $list_detail_mutasi_barang = DetailMutasiBarang::all();
            if ($list_detail_mutasi_barang) {
                return DetailMutasiBarangResource::collection($list_detail_mutasi_barang);
            }
            return response()->json([
                'message' => 'Data Detail Mutasi Barang tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function detail($id)
    {
        if ($this->user->can('Edit Mutasi Barang')) :
            $list_detail_mutasi_barang = DetailMutasiBarang::where('id_mutasi_barang', $id)->orderBy('id', 'asc')->get();
            $list_detail_mutasi_barang = $list_detail_mutasi_barang->sortBy(function ($list_detail_mutasi_barang) {
                return $list_detail_mutasi_barang->kode_barang;
            });

            if ($list_detail_mutasi_barang) {
                return DetailMutasiBarangResource::collection($list_detail_mutasi_barang);
            }
            return response()->json([
                'message' => 'Data Detail Mutasi Barang tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function store(Request $request)
    {
        if ($this->user->can('Tambah Mutasi Barang')) :
            $this->validate($request, [
                'id_mutasi_barang' => 'required|numeric|min:0|max:9999999999',
                'id_stock' => 'required|numeric|min:0|max:9999999999',
                'qty' => 'required|numeric|min:0|max:9999999999',
                'qty_pcs' => 'required|numeric|min:0|max:9999999999'
            ]);

            $mutasi_barang = MutasiBarang::find($request->id_mutasi_barang);

            if ($mutasi_barang->is_approved == 1) {
                return response()->json([
                    'message' => 'Anda tidak boleh menambahkan data barang pada Mutasi Barang yang telah disetujui.'
                ], 422);
            }

            $stock = Stock::find($request->id_stock);
            if ($stock->id_gudang != $mutasi_barang->dari) {
                return response()->json([
                    'message' => 'Stock dan gudang tidak sesuai'
                ], 422);
            }

            $detail_mutasi_barang = DetailMutasiBarang::where('id_mutasi_barang', $request->id_mutasi_barang)->where('id_stock', $request->id_stock)->get()->count();
            if ($detail_mutasi_barang > 0) {
                return response()->json([
                    'message' => 'Item duplikat, cek barang sebelumnya'
                ], 422);
            }

            $input = $request->all();
            $input['created_by'] = $this->user->id;

            try {
                $detail_mutasi_barang = DetailMutasiBarang::create($input);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            $list_detail_mutasi_barang = DetailMutasiBarang::where('id_mutasi_barang', $request->id_mutasi_barang)->orderBy('id', 'asc')->get();
            $new_list_detail_mutasi_barang = DetailMutasiBarangResource::collection($list_detail_mutasi_barang);

            return response()->json([
                'message' => 'Data Detail Mutasi Barang berhasil disimpan.',
                'data' => $new_list_detail_mutasi_barang
            ], 201);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function show($id)
    {
        if ($this->user->can('Edit Mutasi Barang')) :
            $detail_mutasi_barang = DetailMutasiBarang::find($id);

            if ($detail_mutasi_barang) {
                return new DetailMutasiBarangResource($detail_mutasi_barang);
            }
            return response()->json([
                'message' => 'Data Detail Mutasi Barang tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function update(Request $request, $id)
    {
        if ($this->user->can('Update Mutasi Barang')) :
            $detail_mutasi_barang = DetailMutasiBarang::find($id);

            $this->validate($request, [
                'id_mutasi_barang' => 'required|numeric|min:0|max:9999999999',
                'id_stock' => 'required|numeric|min:0|max:9999999999',
                'qty' => 'required|numeric|min:0|max:9999999999',
                'qty_pcs' => 'required|numeric|min:0|max:9999999999'
            ]);

            $mutasi_barang = MutasiBarang::find($detail_mutasi_barang->id_mutasi_barang);

            $stock = Stock::find($request->id_stock);
            if ($stock->id_gudang != $mutasi_barang->dari) {
                return response()->json([
                    'message' => 'Stock dan gudang tidak sesuai, refresh browser'
                ], 422);
            }

            if ($mutasi_barang->is_approved == 1) {
                return response()->json([
                    'message' => 'Anda tidak boleh mengubah data barang pada Mutasi Barang yang telah disetujui.'
                ], 422);
            }

            $input = $request->all();
            $input['updated_by'] = $this->user->id;

            if ($detail_mutasi_barang) {
                $detail_mutasi_barang->update($input);

                $list_detail_mutasi_barang = DetailMutasiBarang::where('id_mutasi_barang', $request->id_mutasi_barang)->get()->sortBy('kode_barang');
                $new_list_detail_mutasi_barang = DetailMutasiBarangResource::collection($list_detail_mutasi_barang);

                return response()->json([
                    'message' => 'Data Detail Mutasi Barang telah berhasil diubah.',
                    'data' => $new_list_detail_mutasi_barang
                ], 201);
            }

            return response()->json([
                'message' => 'Data Detail Mutasi Barang tidak ditemukan.'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function destroy($id, Request $request)
    {
        if ($this->user->can('Hapus Mutasi Barang')) :
            $detail_mutasi_barang = DetailMutasiBarang::find($id);

            $mutasi_barang = MutasiBarang::find($detail_mutasi_barang->id_mutasi_barang);

            if ($mutasi_barang->is_approved == 1) {
                return response()->json([
                    'message' => 'Anda tidak boleh menghapus data barang pada Mutasi Barang yang telah disetujui.'
                ], 422);
            }

            if ($detail_mutasi_barang) {
                // $data = ['deleted_by' => $this->user->id];
                // $detail_mutasi_barang->update($data);
                $detail_mutasi_barang->delete();

                $list_detail_mutasi_barang = DetailMutasiBarang::where('id_mutasi_barang', $detail_mutasi_barang->id_mutasi_barang)->get()->sortBy('kode_barang');
                $new_list_detail_mutasi_barang = DetailMutasiBarangResource::collection($list_detail_mutasi_barang);

                return response()->json([
                    'message' => 'Data Detail Mutasi Barang berhasil dihapus.',
                    'data' => $new_list_detail_mutasi_barang
                ], 200);
            }

            return response()->json([
                'message' => 'Data Detail Mutasi Barang tidak ditemukan!'
            ], 404);
        else :
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

    //     $detail_mutasi_barang = DetailMutasiBarang::withTrashed()->find($id);

    //     if($detail_mutasi_barang) {
    //         $data = ['deleted_by' => null];
    //         $detail_mutasi_barang->update($data);

    //         $detail_mutasi_barang->restore();

    //         return response()->json([
    //             'message' => 'Data Detail Mutasi Barang berhasil dikembalikan.'
    //         ], 200);
    //     }

    //     return response()->json([
    //         'message' => 'Data Detail Mutasi Barang tidak ditemukan!'
    //     ], 404);
    // }

    public function generatePDF($id)
    {
        $mutasi = MutasiBarang::findOrFail($id);
        $dari = \App\Models\Gudang::where('id', $mutasi->dari)->first();
        $ke = \App\Models\Gudang::where('id', $mutasi->ke)->first();
        $detail = \App\Models\DetailMutasiBarang::where('id_mutasi_barang', $id)->get();
        $r_detail_mutasi = DetailMutasiBarangResource::collection($detail);
        $r_mutasi = new MutasiBarangResource($mutasi);
        $user = $this->jwt->user();
        $pdf = PDF::loadView('pdf.detail-mutasi', compact('mutasi', 'r_mutasi', 'detail', 'r_detail_mutasi', 'dari', 'ke', 'user'));
        // return $pdf->download('detail-mutasi-'.$id.'-.pdf');
        return $pdf->stream('mutasi-' . $id . '-.pdf');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\DetailPenerimaanBarang;
use App\Models\PenerimaanBarang;
use App\Models\HargaBarang;
use App\Http\Resources\PenerimaanBarang as PenerimaanBarangResource;
use App\Http\Resources\DetailPenerimaanBarang as DetailPenerimaanBarangResource;
use Barryvdh\DomPDF\Facade\Pdf;

class DetailPenerimaanBarangController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index()
    {
        if ($this->user->can('Menu Penerimaan Barang')):
            $list_detail_penerimaan_barang = DetailPenerimaanBarang::all()->sortBy('kode_barang');

            if ($list_detail_penerimaan_barang) {
                return DetailPenerimaanBarangResource::collection($list_detail_penerimaan_barang);
            }
            return response()->json([
                'message' => 'Data Detail Penerimaan Barang tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function detail($id)
    {
        if ($this->user->can('Edit Penerimaan Barang')):
            $list_detail_penerimaan_barang = DetailPenerimaanBarang::where('id_penerimaan_barang', $id)->get()->sortBy('kode_barang');
            if ($list_detail_penerimaan_barang) {
                return DetailPenerimaanBarangResource::collection($list_detail_penerimaan_barang);
            }
            return response()->json([
                'message' => 'Data Detail Penerimaan Barang tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function store(Request $request)
    {
        if ($this->user->can('Tambah Penerimaan Barang')):
            $penerimaan_barang = PenerimaanBarang::find($request->id_penerimaan_barang);

            if($penerimaan_barang->is_approved == 1) {
                return response()->json([
                    'message' => 'Anda tidak boleh menambahkan data barang pada Penerimaan Barang yang telah disetujui.'
                ], 422);
            }

            $this->validate($request, [
                'id_penerimaan_barang' => 'required|numeric|min:0|max:9999999999',
                'id_barang' => 'required|numeric|min:0|max:9999999999',
                // 'id_harga' => 'numeric|min:0|max:9999999999',
                'qty' => 'required|numeric|min:0|max:9999999999',
                'qty_pcs' => 'required|numeric|min:0|max:9999999999'
            ]);

            $input = $request->all();
            $input['created_by'] = $this->user->id;

            $harga_barang = HargaBarang::where('tipe_harga', 'dbp')->where('id_barang',$request->id_barang)->latest()->first();
            if(!$harga_barang){
                $id_harga = 0;
            }
            $input['id_harga'] = $harga_barang->id ?? 0;


            try {
                $detail_penerimaan_barang = DetailPenerimaanBarang::create($input);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            $list_detail_penerimaan_barang = DetailPenerimaanBarang::where('id_penerimaan_barang', $request->id_penerimaan_barang)->get()->sortBy('kode_barang');
            $new_list_detail_penerimaan_barang = DetailPenerimaanBarangResource::collection($list_detail_penerimaan_barang);

            return response()->json([
                'message' => 'Data Detail Penerimaan Barang berhasil disimpan.',
                'data' => $new_list_detail_penerimaan_barang
            ], 201);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function show($id)
    {
        if ($this->user->can('Edit Penerimaan Barang')):
            $detail_penerimaan_barang = DetailPenerimaanBarang::find($id);

            if ($detail_penerimaan_barang) {
                return new DetailPenerimaanBarangResource($detail_penerimaan_barang);
            }
            return response()->json([
                'message' => 'Data Detail Penerimaan Barang tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function update(Request $request, $id)
    {
        if ($this->user->can('Update Penerimaan Barang')):
            $detail_penerimaan_barang = DetailPenerimaanBarang::find($id);
            $penerimaan_barang = PenerimaanBarang::find($detail_penerimaan_barang->id_penerimaan_barang);

            if($penerimaan_barang->is_approved == 1) {
                return response()->json([
                    'message' => 'Anda tidak boleh mengubah data barang pada Penerimaan Barang yang telah disetujui.'
                ], 422);
            }

            $this->validate($request, [
                'id_penerimaan_barang' => 'required|numeric|min:0|max:9999999999',
                'id_barang' => 'required|numeric|min:0|max:9999999999',
                // 'id_harga' => 'numeric|min:0|max:9999999999',
                'qty' => 'required|numeric|min:0|max:9999999999',
                'qty_pcs' => 'required|numeric|min:0|max:9999999999'
            ]);

            $input = $request->all();
            $input['updated_by'] = $this->user->id;

            if($request->id_barang != $detail_penerimaan_barang->id_barang){
                $harga_barang = HargaBarang::where('tipe_harga', 'dbp')->where('id_barang',$request->id_barang)->latest()->first();
                if(!$harga_barang){
                    $id_harga = 0;
                }
                $input['id_harga'] = $harga_barang->id;
            }
            else{
                $input['id_harga'] = $detail_penerimaan_barang->id_harga;
            }

            if ($detail_penerimaan_barang) {
                $detail_penerimaan_barang->update($input);

                $list_detail_penerimaan_barang = DetailPenerimaanBarang::where('id_penerimaan_barang', $request->id_penerimaan_barang)->get()->sortBy('kode_barang');
                $new_list_detail_penerimaan_barang = DetailPenerimaanBarangResource::collection($list_detail_penerimaan_barang);

                return response()->json([
                    'message' => 'Data Detail Penerimaan Barang telah berhasil diubah.',
                    'data' => $new_list_detail_penerimaan_barang
                ], 201);
            }

            return response()->json([
                'message' => 'Data Detail Penerimaan Barang tidak ditemukan.'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function update_harga(Request $request, $id)
    {
        if ($this->user->can('Update Harga Penerimaan Barang')):
            $detail_penerimaan_barang           = DetailPenerimaanBarang::find($id);
            $detail_penerimaan_barang->id_harga = $request->id_harga;
            $detail_penerimaan_barang->save();
            $list_detail_penerimaan_barang      = DetailPenerimaanBarang::where('id_penerimaan_barang', $request->id_penerimaan_barang)->get()->sortBy('kode_barang');
            $new_list_detail_penerimaan_barang  = DetailPenerimaanBarangResource::collection($list_detail_penerimaan_barang);

            return response()->json([
                'message' => 'Data Detail Penerimaan Barang telah berhasil diubah.',
                'data' => $new_list_detail_penerimaan_barang
            ], 201);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function destroy($id, Request $request)
    {
        if ($this->user->can('Hapus Penerimaan Barang')):
            $detail_penerimaan_barang = DetailPenerimaanBarang::find($id);

            $penerimaan_barang = PenerimaanBarang::find($detail_penerimaan_barang->id_penerimaan_barang);

            if($penerimaan_barang->is_approved == 1) {
                return response()->json([
                    'message' => 'Anda tidak boleh menghapus data barang pada Penerimaan Barang yang telah disetujui.'
                ], 422);
            }

            if($detail_penerimaan_barang) {
                // $data = ['deleted_by' => $this->user->id];
                // $detail_penerimaan_barang->update($data);
                $detail_penerimaan_barang->delete();

                $list_detail_penerimaan_barang = DetailPenerimaanBarang::where('id_penerimaan_barang', $detail_penerimaan_barang->id_penerimaan_barang)->get()->sortBy('kode_barang');
                $new_list_detail_penerimaan_barang = DetailPenerimaanBarangResource::collection($list_detail_penerimaan_barang);

                return response()->json([
                    'message' => 'Data Detail Penerimaan Barang berhasil dihapus.',
                    'data' => $new_list_detail_penerimaan_barang
                ], 200);
            }

            return response()->json([
                'message' => 'Data Detail Penerimaan Barang tidak ditemukan!'
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

    public function generatePDF($id){
        if ($this->user->can('Download Penerimaan Barang')):
            $penerimaan = \App\Models\PenerimaanBarang::join('gudang', 'gudang.id', '=', 'penerimaan_barang.id_gudang')
                ->join('users', 'users.id', '=', 'penerimaan_barang.created_by')
                ->join('principal', 'principal.id', '=', 'penerimaan_barang.id_principal')
                ->where('penerimaan_barang.id', $id)
                ->select('users.name as create_by_name', 'users.*', 'gudang.*', 'penerimaan_barang.*', 'principal.*', 'principal.id as principal_id')
                ->first();
            // dd($penerimaan);
            $rr_penerimaan = PenerimaanBarang::findOrFail($id);
            $r_penerimaan = new PenerimaanBarangResource($rr_penerimaan);
            $detail = \App\Models\DetailPenerimaanBarang::where('id', $id)->get();
            $r_detail = DetailPenerimaanBarangResource::collection($detail);
            $user = $this->jwt->user();
            // return $r_detail;
            // return $r_penerimaan;
            $pdf = PDF::loadView('pdf.detail-penerimaan', compact('penerimaan', 'r_penerimaan', 'detail', 'r_detail', 'user'));
            // return $pdf->download('detail-penerimaan-'.$id.'-.pdf');
            return $pdf->stream('penerimaan_barang-' . $id . '-.pdf');
        else:
            return $this->Unauthorized();
        endif;
    }

    public function list_barang_by_id(Request $request)
    {
        $id_penerimaan_barang       = $request->id_penerimaan_barang;
        $detail_penerimaan_barang   = DetailPenerimaanBarang::with(['barang'])->whereIn('id_penerimaan_barang', $id_penerimaan_barang)->get();
        $detail = [];
        foreach ($detail_penerimaan_barang as $key => $dpb) {
            $detail[] = [
                'id_barang'     => $dpb->barang->id,
                'kode_barang'   => $dpb->barang->kode_barang,
                'nama_barang'   => $dpb->barang->nama_barang,
                'harga_barang'  => $dpb->price_before_tax,
                'subtotal'      => $dpb->subtotal,
                'qty'           => $dpb->qty,
                'pcs'           => $dpb->qty_pcs,
                'disc_persen'   => 0,
                'disc_value'    => 0
            ];
        }

        $detail     = collect($detail);
        $detail     = $detail->groupBy('kode_barang');
        $detail_out = [];
        foreach ($detail as $key => $dtl) {
            $detail_out[] = [
                'id_barang'     => $dtl[0]['id_barang'],
                'kode_barang'   => $key,
                'nama_barang'   => $dtl[0]['nama_barang'],
                'harga_barang'  => $dtl[0]['harga_barang'],
                'subtotal'      => $dtl->sum('subtotal'),
                'qty'           => $dtl->sum('qty'),
                'pcs'           => $dtl->sum('pcs'),
                'disc_persen'   => 0,
                'disc_value'    => 0
            ];
        }

        return response()->json(['data' => $detail_out], 200);
    }
}

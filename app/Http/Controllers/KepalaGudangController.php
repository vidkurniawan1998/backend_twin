<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\KepalaGudang;
use App\Models\User;
use App\Models\Gudang;
use App\Http\Resources\KepalaGudang as KepalaGudangResource;

class KepalaGudangController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        $list_kepala_gudang = KepalaGudang::orderBy('user_id', 'asc');

        $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';

        $list_kepala_gudang = $perPage == 'all' ? $list_kepala_gudang->get() : $list_kepala_gudang->paginate((int)$perPage);

        if ($list_kepala_gudang) {
            return KepalaGudangResource::collection($list_kepala_gudang);
        }
        return response()->json([
            'message' => 'Data Kepala Gudang tidak ditemukan!'
        ], 404);
    }

    public function show($id)
    {
        $kepala_gudang = KepalaGudang::find($id);

        if ($kepala_gudang) {
            return new KepalaGudangResource($kepala_gudang);

        }
        return response()->json([
            'message' => 'Data Kepala Gudang tidak ditemukan!'
        ], 404);
    }

    public function update(Request $request, $id)
    {
        if ($this->user->role != 'admin' && $this->user->role != 'pimpinan'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
            ], 400);
        }

        $kepala_gudang = KepalaGudang::find($id);

        $this->validate($request, [
            'id_gudang' => 'nullable|numeric|min:0|max:9999999999|unique:kepala_gudang,id_gudang,'.$id.',user_id'
        ]);

        $input = $request->all();

        if ($kepala_gudang) {
            $kepala_gudang->update($input);
    
            return response()->json([
                'message' => 'Data Kepala Gudang telah berhasil diubah.'
            ], 201);
        }
    
        return response()->json([
            'message' => 'Data Kepala Gudang tidak ditemukan.'
        ], 404);
    }

    //fungsi get list gudang baik yang gk ada kepala gudangnya
    public function getListGudang(){
        //get id_gudang yang sudah ada kepala gudangnya
        $id_gudang = KepalaGudang::pluck('id_gudang');
        //get data gudang except id_gudang
        $list_gudang = Gudang::whereNotIn('id',$id_gudang)->where('jenis', 'baik')->get();
        return $list_gudang;
    }
}
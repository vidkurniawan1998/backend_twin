<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\SalesSupervisor;
use App\Http\Resources\SalesSupervisor as SalesSupervisorResource;

class SalesSupervisorController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        $list_ss = SalesSupervisor::with('user')->orderBy('user_id', 'asc');

        $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
        $list_ss = $perPage == 'all' ? $list_ss->get()->sortBy('tim') : $list_ss->paginate((int)$perPage);

        if ($list_ss) {
            return SalesSupervisorResource::collection($list_ss);
        }
        return response()->json([
            'message' => 'Data Sales Supervisor tidak ditemukan!'
        ], 404);
    }

    public function show($id)
    {
        $ss = SalesSupervisor::find($id);

        if ($ss) {
            return new SalesSupervisorResource($ss);
        }
        return response()->json([
            'message' => 'Data Sales Supevisor tidak ditemukan!'
        ], 404);
    }

    // public function update(Request $request, $id)
    // {
    //     if ($this->user->role != 'admin' && $this->user->role != 'pimpinan'){
    //         return response()->json([
    //             'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
    //         ], 400);
    //     }

    //     $salesman = Salesman::find($id);

    //     $this->validate($request, [
    //         // 'kode_salesman' => 'max:20|unique:salesman,kode_salesman,'.$id.',user_id',
    //         'id_tim' => 'numeric|min:0|max:9999999999'
    //     ]);

    //     $input = $request->all();

    //     if ($salesman) {
    //         $salesman->update($input);
    
    //         return response()->json([
    //             'message' => 'Data Salesman telah berhasil diubah.'
    //         ], 201);
    //     }
    
    //     return response()->json([
    //         'message' => 'Data Salesman tidak ditemukan.'
    //     ], 404);
    // }
}
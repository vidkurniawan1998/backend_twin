<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Canvass;
use App\Http\Resources\Canvass as CanvassResource;
use App\Helpers\Helper;

class CanvassController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if ($this->user->can('Menu Canvass')):
            $list_canvass = Canvass::join('tim', 'canvass.id_tim', '=', 'tim.id')
                                    ->leftJoin('salesman', 'salesman.id_tim', '=', 'tim.id')
                                    ->leftJoin('users', 'users.id', '=', 'salesman.user_id')
                                    ->leftJoin('depo', 'depo.id', '=', 'tim.id_depo')
                                    ->leftJoin('perusahaan', 'perusahaan.id', '=', 'depo.id_perusahaan')
                                    ->leftJoin('gudang', 'canvass.id_gudang_canvass', '=', 'gudang.id')
                                    ->leftJoin('kendaraan', 'canvass.id_kendaraan', '=', 'kendaraan.id');
                                    // ->select('id_tim', 'id_gudang_canvass', 'id_kendaraan'
                                    //         // 'tim.nama_tim'
                                    //         // 'users.name',
                                    //         // 'depo.nama_depo',
                                    //         // 'gudang.nama_gudang',
                                    //         // 'kendaraan.no_pol_kendaraan', 'kendaran.body_no'
                                    // );

            // return $list_canvass->get();
            //Filter By Hak akses depo
            $depo_user      = Helper::depoIDByUser($this->user->id);
            $list_canvass = $list_canvass->whereIn('depo.id', $depo_user);

            if($request->keyword != ''){
                $keyword = $request->keyword;
                $list_canvass = $list_canvass->where(function ($query) use ($keyword){
                    $query->where('tim.nama_tim', 'like', '%' . $keyword . '%')
                    ->orWhere('users.name', 'like', '%' . $keyword . '%')
                    ->orWhere('depo.nama_depo', 'like', '%' . $keyword . '%')
                    ->orWhere('gudang.nama_gudang', 'like', '%' . $keyword . '%')
                    ->orWhere('kendaraan.no_pol_kendaraan', 'like', '%' . $keyword . '%')
                    ->orWhere('kendaraan.body_no', 'like', '%' . $keyword . '%');
                });
            }
            $list_canvass->orderBy('tim.nama_tim');

            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            $list_canvass = $perPage == 'all' ? $list_canvass->get() : $list_canvass->paginate((int)$perPage);

            if ($list_canvass) {
                return CanvassResource::collection($list_canvass);
            }
            return response()->json([
                'message' => 'Data Canvass tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function show($id)
    {
        if ($this->user->can('Edit Canvass')):
            $canvass = Canvass::join('tim', 'canvass.id_tim', '=', 'tim.id')
            ->leftJoin('salesman', 'salesman.id_tim', '=', 'tim.id')
            ->leftJoin('users', 'users.id', '=', 'salesman.user_id')
            ->leftJoin('depo', 'depo.id', '=', 'tim.id_depo')
            ->leftJoin('gudang', 'canvass.id_gudang_canvass', '=', 'gudang.id')
            ->leftJoin('kendaraan', 'canvass.id_kendaraan', '=', 'kendaraan.id')->find($id); //->toSql()

            // $canvass = Canvass::with('tim.salesman.user')->with('tim.salesman.depo')->with('gudang_canvass')->with('kendaraan')->find($id);
            // $canvass = Canvass::with('tim.salesman.users')->with('kendaraan')->with('gudang.depo')->find($id);


            if ($canvass) {
                return $canvass;
                return new CanvassResource($canvass);

            }
            return response()->json([
                'message' => 'Data Canvass tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function update(Request $request, $id)
    {
        if ($this->user->can('Update Canvass')):
            $canvass = Canvass::find($id);

            $input = $request->all();

            if ($canvass) {
                $canvass->update($input);

                return response()->json([
                    'message' => 'Data Canvass telah berhasil diubah.',
                    'data' => $canvass
                ], 201);
            }

            return response()->json([
                'message' => 'Data Canvass tidak ditemukan.'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }
}

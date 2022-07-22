<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\User;
use App\Http\Resources\Checker as CheckerResource;
use App\Helpers\Helper;
use Carbon\Carbon;
use App\Models\Penjualan;

class CheckerController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request){
        $id_gudang = $request->has('gudang') ? $request->gudang: Helper::gudangByUser($this->user->id);
        $list_checker = User::join('users_roles', 'users.id', '=', 'users_roles.user_id')
                                ->join('user_gudang','users.id', '=', 'user_gudang.user_id')
                                ->whereIn('user_gudang.gudang_id', $id_gudang)
                                ->where('users_roles.role_id', 18);

        if($request->keyword != ''){
            $keyword = $request->keyword;
            $list_checker = $list_checker->where(function ($query) use ($keyword){
                $query->where('users.name', 'like', '%' . $keyword . '%')
                ->orWhere('users.email', 'like', '%' . $keyword . '%')
                ->orWhere('users.phone', 'like', '%' . $keyword . '%');
            });
        }

        $id_checker = $list_checker->pluck('users.id');

        $list_checker = User::whereIn('id', $id_checker);

        $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 5;
        $list_checker = $perPage == 'all' ? $list_checker->get() : $list_checker->paginate((int)$perPage);

        if ($list_checker) {
            return CheckerResource::collection($list_checker);
        }
        return response()->json([
            'message' => 'Data Checker tidak ditemukan!'
        ], 404);

    }

    // Mencari driver yang sesuai dengan akses depo pada id_depo penjualan
    public function get_checker_by_id_gudang_penjualan(Request $request, $id)
    {
        $id = explode(",",$id);
        $id_gudang = Penjualan::whereIn('id',$id)->pluck('id_gudang');
        $list_checker = User::join('users_roles', 'users.id', '=', 'users_roles.user_id')
                                ->join('user_gudang','users.id', '=', 'user_gudang.user_id')
                                ->whereIn('user_gudang.gudang_id', $id_gudang)
                                ->where('users_roles.role_id', 18);

        if($request->keyword != ''){
            $keyword = $request->keyword;
            $list_checker = $list_checker->where(function ($query) use ($keyword){
                $query->where('users.name', 'like', '%' . $keyword . '%')
                ->orWhere('users.email', 'like', '%' . $keyword . '%')
                ->orWhere('users.phone', 'like', '%' . $keyword . '%');
            });
        }

        $id_checker = $list_checker->pluck('users.id');

        $list_checker = User::whereIn('id', $id_checker)->get();

        if ($list_checker) {
            return CheckerResource::collection($list_checker);
        }
        return response()->json([
            'message' => 'Data Checker tidak ditemukan!'
        ], 404);
    }
}
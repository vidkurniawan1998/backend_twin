<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Driver;
use App\Models\User;
use App\Http\Resources\Driver as DriverResource;
use App\Helpers\Helper;
use Carbon\Carbon;
use App\Models\Penjualan;

class DriverController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request){
        // $list_driver = Driver::with('user');
        $id_depo = $request->has('depo') ? $request->depo: Helper::depoIDByUser($this->user->id);
        $list_driver = Driver::join('users', 'driver.user_id', '=', 'users.id')
                                ->leftjoin('user_depo','user_depo.user_id', '=', 'driver.user_id')
                                ->whereIn('depo_id', $id_depo);

        if($this->user->can('Menu Driver')):
            if($request->keyword != ''){
                $keyword = $request->keyword;
                $list_driver = $list_driver->where(function ($query) use ($keyword){
                    $query->where('users.name', 'like', '%' . $keyword . '%')
                    ->orWhere('users.email', 'like', '%' . $keyword . '%')
                    ->orWhere('users.phone', 'like', '%' . $keyword . '%');
                });
            }
    
            $id_driver = $list_driver->orderBy('users.name')->pluck('driver.user_id');

            $list_driver = Driver::join('users', 'driver.user_id', '=', 'users.id')
                                ->join('user_perusahaan','user_perusahaan.user_id', '=', 'driver.user_id')
                                ->join('perusahaan as p','user_perusahaan.perusahaan_id', '=', 'p.id')
                                ->whereIn('driver.user_id', $id_driver)
                                ->select('driver.*','users.name','users.email','p.kode_perusahaan','p.nama_perusahaan');

            $list_driver->orderBy('users.name');

            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 5;
            $list_driver = $perPage == 'all' ? $list_driver->get() : $list_driver->paginate((int)$perPage);

            if ($list_driver) {
                return DriverResource::collection($list_driver);
            }
            return response()->json([
                'message' => 'Data Driver tidak ditemukan!'
            ], 404);
        else: 
            return $this->Unauthorized();
        endif;
    }

    public function show($id){
        if($this->user->can('Edit Driver')):
            // $driver = Driver::with('user')->find($id);
            $driver = Driver::join('users', 'driver.user_id', '=', 'users.id')
                            ->join('user_perusahaan','user_perusahaan.user_id', '=', 'driver.user_id')
                            ->join('perusahaan as p','user_perusahaan.perusahaan_id', '=', 'p.id')
                            ->select('driver.*','users.name','users.email','p.kode_perusahaan','p.nama_perusahaan')
                            ->find($id);
    
            if ($driver) {
                return new DriverResource($driver);
            }
            return response()->json([
                'message' => 'Data Driver tidak ditemukan!'
            ], 404);
        else: 
            return $this->Unauthorized();
        endif;
    }

    public function driver_distribusi(Request $request)
    {
        if($this->user->hasRole('Checker')):
            $id_gudang = Helper::gudangByUser($this->user->id);
            $id_driver = Driver::join('users', 'driver.user_id', '=', 'users.id')
                                    ->join('user_gudang','user_gudang.user_id', '=', 'driver.user_id')
                                    ->join('penjualan','driver.user_id','=','penjualan.driver_id')
                                    ->whereIn('id_gudang', $id_gudang )
                                    ->whereNotNull('penjualan.driver_id')->whereNotNull('penjualan.tanggal_jadwal')
                                    ->where('penjualan.status', 'approved')
                                    ->whereDate('penjualan.tanggal_jadwal', Carbon::today())
                                    ->groupBy('penjualan.id')
                                    ->pluck('driver.user_id');

           
            $list_driver = Driver::join('users', 'driver.user_id', '=', 'users.id')
                                    ->join('user_perusahaan','user_perusahaan.user_id', '=', 'driver.user_id')
                                    ->join('perusahaan as p','user_perusahaan.perusahaan_id', '=', 'p.id')
                                    ->whereIn('driver.user_id', $id_driver)
                                    ->orderBy('users.name')
                                    ->select('driver.*','users.name','users.email','p.kode_perusahaan','p.nama_perusahaan');

            if($request->keyword != ''){
                $keyword = $request->keyword;
                $list_driver = $list_driver->where(function ($query) use ($keyword){
                    $query->where('users.name', 'like', '%' . $keyword . '%')
                    ->orWhere('users.email', 'like', '%' . $keyword . '%')
                    ->orWhere('users.phone', 'like', '%' . $keyword . '%');
                });
            }
    
            $list_driver->orderBy('users.name');
    
            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 5;
            $list_driver = $perPage == 'all' ? $list_driver->get() : $list_driver->paginate((int)$perPage);

            if ($list_driver) {
                return response()->json([
                    'data' => DriverResource::collection($list_driver)
                ], 200);
            }
            return response()->json([
                'message' => 'Data Driver tidak ditemukan!'
            ], 404);
            
        else: 
            return $this->Unauthorized();
        endif;
    }
    // Mencari driver yang sesuai dengan akses depo pada id_depo penjualan
    public function get_driver_by_id_depo_penjualan(Request $request, $id)
    {
        $id = explode(",",$id);
        // $id_depo = Helper::depoIDByUser($this->user->id);
        $id_perusahaan = Penjualan::select('id_perusahaan')->whereIn('id',$id)->pluck('id_perusahaan');
        // $list_driver = Driver::join('users', 'driver.user_id', '=', 'users.id')
        //                         ->leftjoin('user_depo','user_depo.user_id', '=', 'driver.user_id')
        //                         ->join('user_perusahaan','user_perusahaan.user_id', '=', 'driver.user_id')
        //                         ->whereIn('user_depo.depo_id', $id_depo);
                                // ->whereIn('user_perusahaan.perusahaan_id', $id_perusahaan);

        // if($this->user->can('Menu Driver')):
            // if($request->keyword != ''){
            //     $keyword = $request->keyword;
            //     $list_driver = $list_driver->where(function ($query) use ($keyword){
            //         $query->where('users.name', 'like', '%' . $keyword . '%')
            //         ->orWhere('users.email', 'like', '%' . $keyword . '%')
            //         ->orWhere('users.phone', 'like', '%' . $keyword . '%');
            //     });
            // }
    
            // $id_driver = $list_driver->pluck('driver.user_id');

            $list_driver = Driver::join('users', 'driver.user_id', '=', 'users.id')
                                    ->join('user_perusahaan','user_perusahaan.user_id', '=', 'driver.user_id')
                                    ->join('perusahaan as p','user_perusahaan.perusahaan_id', '=', 'p.id')
                                    ->whereIn('p.id', $id_perusahaan)
                                    ->orderBy('users.name')
                                    ->select('driver.*','users.name','users.email','p.nama_perusahaan','p.kode_perusahaan')
                                    ->get();

            if ($list_driver) {
                return DriverResource::collection($list_driver);
            }

            return response()->json([
                'message' => 'Data Driver tidak ditemukan!'
            ], 404);
        // else: 
        //     return $this->Unauthorized();
        // endif;
    }

    // Mencari driver yang sesuai dengan akses depo pada id_depo
    public function get_driver_by_id_depo(Request $request, $id)
    {
        // $list_driver = Driver::join('users', 'driver.user_id', '=', 'users.id')
        //                         ->leftjoin('user_depo','user_depo.user_id', '=', 'driver.user_id')
        //                         ->where('depo_id', $id);

        // if($request->keyword != ''){
        //     $keyword = $request->keyword;
        //     $list_driver = $list_driver->where(function ($query) use ($keyword){
        //         $query->where('users.name', 'like', '%' . $keyword . '%')
        //         ->orWhere('users.email', 'like', '%' . $keyword . '%')
        //         ->orWhere('users.phone', 'like', '%' . $keyword . '%');
        //     });
        // }

        // $id_driver = $list_driver->pluck('driver.user_id');

        $list_driver = Driver::join('users', 'driver.user_id', '=', 'users.id')
                                ->join('user_perusahaan','user_perusahaan.user_id', '=', 'driver.user_id')
                                ->join('perusahaan as p','user_perusahaan.perusahaan_id', '=', 'p.id')
                                ->join('depo as d','p.id', '=', 'd.id_perusahaan')
                                ->where('d.id', $id)
                                ->orderBy('users.name')
                                ->select('driver.*','users.name','users.email','p.kode_perusahaan','p.nama_perusahaan')
                                ->get();

        // $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 5;
        // $list_driver = $perPage == 'all' ? $list_driver->get() : $list_driver->paginate((int)$perPage);

        if ($list_driver) {
            return DriverResource::collection($list_driver);
        }
        return response()->json([
            'message' => 'Data Driver tidak ditemukan!'
        ], 404);
    
    }
    

}
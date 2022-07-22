<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Penjualan;
use App\Models\Depo;
use Carbon\Carbon;
use App\Helpers\Helper;

class ServiceLevelController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if ($this->user->cannot('Menu Service Level Monitoring')){ return $this->Unauthorized(); }
        
        $id_user        = $this->user->id;
        $start_date     = $request->has('start_date') ? $request->start_date : Carbon::now()->toDateString();
        $end_date       = $request->has('end_date')   ? $request->end_date   : Carbon::now()->toDateString();
        $id_perusahaan  = $request->has('id_perusahaan') ? is_array($request->id_perusahaan) ? 
                          $request->id_perusahaan : [$request->id_perusahaan] : Helper::perusahaanByUser($id_user);
        $id_depo        = $request->has('id_depo') ? is_array($request->id_depo) ?  
                          $request->id_depo : [$request->id_depo] : Helper::depoIDByUser($id_user, $id_perusahaan);
        $id_salesman    = $request->has('id_salesman') ? is_array($request->id_salesman) ? $request->id_salesman : [$request->id_salesman] : [];
        $data = Depo::leftJoin('penjualan','depo.id','penjualan.id_depo')
        ->leftJoin('detail_penjualan','detail_penjualan.id_penjualan','penjualan.id')
        ->leftJoin('perusahaan','perusahaan.id','penjualan.id_perusahaan')
        ->whereNull('penjualan.deleted_at')
        ->whereBetween('tanggal',[$start_date,$end_date])
        ->when(count($id_perusahaan)>0, function ($q) use ($id_perusahaan){
            return $q->whereIn('penjualan.id_perusahaan',$id_perusahaan);
        })
        ->when(count($id_depo)>0, function ($q) use ($id_depo){
            return $q->whereIn('penjualan.id_depo',$id_depo);
        })
        ->when(count($id_salesman)>0, function ($q) use ($id_salesman){
            return $q->whereIn('p.id_salesman',$id_salesman);
        })
        ->selectRaw('SUM(qty) as total_qty, SUM(qty_pcs) as total_qty_pcs, status, CONCAT(nama_depo, " ", kode_perusahaan) as nama_depo')
        ->groupBy('penjualan.status')
        ->groupBy('depo.id')
        ->orderBy('penjualan.id_depo')
        ->get();

        $total = $data->groupBy('status')->map(function ($row) {
            return [
                'total_qty'     => $row->sum('total_qty'),
                'total_qty_pcs' => $row->sum('total_qty_pcs'),
            ];
        });

        $res   = $data->groupBy(['nama_depo','status']);
        return response()->json([
            'data'  => $res,
            'total' => $total
        ]);
    }

}

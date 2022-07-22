<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\User;
use App\Helpers\Helper;
use Carbon\Carbon;
use App\Models\Penjualan;
use Illuminate\Support\Facades\DB;

class KpiController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if ($this->user->cannot('Menu Laporan Kpi')){
            return $this->Unauthorized();
        }

        $this->validate($request, [
            'id_brand' => 'required'
        ]);

        $id_user        = $this->user->id;
        $start_date     = $request->has('start_date') ? $request->start_date : Carbon::now()->toDateString();
        $end_date       = $request->has('end_date') ? $request->end_date : Carbon::now()->toDateString();
        $id_perusahaan  = $request->has('id_perusahaan') && count($request->id_perusahaan)>0 ? $request->id_perusahaan 
                        : Helper::perusahaanByUser($id_user);
        $id_depo        = $request->has('id_depo') ? is_array($request->id_depo) ? count($request->id_depo)>0 ? $request->id_depo 
                        : Helper::depoIDByUser($id_user, $id_perusahaan) : [$request->id_depo] 
                        : Helper::depoIDByUser($id_user, $id_perusahaan) ;
        $id_principal   = $request->has('id_principal') ? is_array($request->id_principal) ? count($request->id_principal)>0 ? 
                          $request->id_principal  : [] : [$request->id_principal] : [] ;
        $id_brand       = $request->has('id_brand') ? is_array($request->id_brand) ? count($request->id_brand)>0 ? $request->id_brand 
                        : [] : [$request->id_brand] : [] ; 

        $date_filter    = [$start_date.' 00:00:00',$end_date. ' 23:59:59'];

        // $pemetaan = DB::table('hari_efektif')
        // ->whereBetween('tanggal', $date_filter)
        // ->select(
        //     DB::raw(
        //         'MAX(tanggal) as tanggal, MAX(minggu) as minggu'
        //     )
        // )
        // ->groupBy('minggu')
        // ->get();

        // $kunjungan = [];
        // foreach ($pemetaan as $row) {
        //     $end_date       = $row->tanggal;
        //     $date_filter    = [$start_date.' 00:00:00',$end_date. ' 23:59:59'];
        //     $kunjungan[] = DB::table('penjualan')
        //     ->join('tim','tim.id','penjualan.id_tim')
        //     ->join('depo','tim.id_depo','depo.id')
        //     ->join('perusahaan','perusahaan.id','depo.id_perusahaan')
        //     ->join('detail_penjualan','detail_penjualan.id_penjualan','penjualan.id')
        //     ->join('stock','detail_penjualan.id_stock','stock.id')
        //     ->join('barang','barang.id','stock.id_barang')        
        //     ->join('segmen','barang.id_segmen','segmen.id')
        //     ->join('brand','brand.id','segmen.id_brand')
        //     ->join('principal','principal.id','brand.id_principal')
        //     ->whereBetween('penjualan.delivered_at', $date_filter)
        //     ->whereNull('penjualan.deleted_at')        
        //      ->when(count($id_perusahaan)>0, function ($q) use ($id_perusahaan){
        //     return $q->whereIn('perusahaan.id',$id_perusahaan);
        //     })
        //     ->when(count($id_depo)>0, function ($q) use ($id_depo){
        //         return $q->whereIn('depo.id',$id_depo);
        //     })
        //     ->when(count($id_principal)>0, function ($q) use ($id_principal){
        //         return $q->whereIn('principal.id',$id_principal);
        //     })
        //     ->when(count($id_brand)>0, function ($q) use ($id_brand){
        //         return $q->whereIn('brand.id',$id_brand);
        //     })
        //     ->select(
        //         DB::raw(
        //             'id_tim, COUNT(DISTINCT(id_toko)) as banyak_toko'
        //         )
        //     )
        //     ->groupBy('penjualan.id_tim');
        //     $start_date     = Carbon::parse($end_date)->addDays(1);
        // }
        // $data_merge = $kunjungan[0];
        // for ($i=1; $i < count($kunjungan) ; $i++) { 
        //     $data_merge = $data_merge->unionAll($kunjungan[$i]);
        // }
        // $data_kunjungan = DB::table($data_merge)
        // ->select(
        //     DB::raw(
        //         'id_tim, SUM(banyak_toko) as banyak_toko'
        //     )
        // )
        // ->groupBy('id_tim');

        //DB::enableQueryLog(); 

         $data = DB::table('penjualan')
        ->join('detail_penjualan','detail_penjualan.id_penjualan','penjualan.id')
        ->join('stock','detail_penjualan.id_stock','stock.id')
        ->join('barang','barang.id','stock.id_barang')
        ->join('tim','tim.id','penjualan.id_tim')
        ->join('depo','tim.id_depo','depo.id')
        ->join('perusahaan','perusahaan.id','depo.id_perusahaan')
        ->join('segmen','barang.id_segmen','segmen.id')
        ->join('brand','brand.id','segmen.id_brand')
        ->join('principal','principal.id','brand.id_principal')
        // ->joinSub($data_kunjungan,'data_kunjungan', function($join){
        //     $join->on('data_kunjungan.id_tim','=','penjualan.id_tim');
        // })
        ->where('penjualan.status','delivered')
        ->whereNull('penjualan.deleted_at')
        ->whereBetween('penjualan.delivered_at', $date_filter)
        ->when(count($id_perusahaan)>0, function ($q) use ($id_perusahaan){
            return $q->whereIn('perusahaan.id',$id_perusahaan);
        })
        ->when(count($id_depo)>0, function ($q) use ($id_depo){
            return $q->whereIn('depo.id',$id_depo);
        })
        ->when(count($id_principal)>0, function ($q) use ($id_principal){
            return $q->whereIn('principal.id',$id_principal);
        })
        ->when(count($id_brand)>0, function ($q) use ($id_brand){
            return $q->whereIn('brand.id',$id_brand);
        })
        ->select(
            DB::raw(
                'penjualan.id_tim,
                nama_depo,
                nama_tim,
                nama_principal,
                nama_brand,                 
                COUNT(DISTINCT penjualan.id) as banyak_nota,
                COUNT(DISTINCT(id_toko)) as banyak_toko,
                SUM(detail_penjualan.qty+(detail_penjualan.qty_pcs/barang.isi)) as qty,
                SUM(
                    ((detail_penjualan.qty+(detail_penjualan.qty_pcs/barang.isi)) * detail_penjualan.harga_jual)/1.1
                ) as subtotal,
                SUM(
                    ((detail_penjualan.qty+(detail_penjualan.qty_pcs/barang.isi)) * detail_penjualan.disc_rupiah )/1.1
                ) as discount_rupiah,
                SUM(
                    ((detail_penjualan.qty+(detail_penjualan.qty_pcs/barang.isi)) * detail_penjualan.harga_jual) / 1.1 * disc_persen / 100
                ) as discount_persen'
            )
        )
        ->groupBy('penjualan.id_tim')
        ->orderBy('tim.id')
        ->get();
        //dd(DB::getQueryLog());
       return response()->json($data);
    }
}

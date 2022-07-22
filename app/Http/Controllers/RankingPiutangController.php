<?php

namespace App\Http\Controllers;


use App\Helpers\Helper;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Penjualan;
use App\Models\Toko;
use App\Http\Resources\PelunasanPenjualan as PelunasanPenjualanResource;

class RankingPiutangController extends Controller
{

    protected $jwt;
    public function __construct(JWTAuth $jwt){
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if ($this->user->can('Menu Laporan Ranking Piutang')):
            $status         = 'delivered';
            $id_user        = $this->user->id;
            $end_date       = $request->end_date;
            $start_date     = $request->start_date;

            $status         = 'delivered';
            $id_toko        = $request->has('id_toko')       && $request->id_toko>0 && $request->id_toko!='' ? $request->id_toko : '';
            $id_perusahaan  = $request->has('id_perusahaan') && count($request->id_perusahaan)>0 ? $request->id_perusahaan : Helper::perusahaanByUser($id_user);
            $id_depo        = $request->has('depo')          && count($request->depo) > 0 ? $request->depo : Helper::depoIDByUser($id_user, $id_perusahaan);
            $keyword        = $request->has('keyword')       && $request->keyword!='' ?  $request->keyword : '';

            $id_salesman    = $request->has('id_salesman') ? 
                              is_array($request->id_salesman) ?
                              count($request->id_salesman)>0 ?
                              $request->id_salesman
                              : []
                              : [$request->id_salesman]
                              : [];

            // $start_date     = $request->has('start_date')    && ($request->start_date!='0000-00-00' && $request->start_date!='' && $request->start_date!=null) ? $request->start_date : Carbon::today()->toDateString();
            // $end_date       = $request->has('end_date')      && ($request->end_date!='0000-00-00' && $request->end_date!='' && $request->end_date!=null) ? $request->end_date : Carbon::today()->toDateString();

            // $start_date     = $start_date.' 00:00:00';
            // $end_date       = $end_date. ' 23:59:59';
            // $date_filter    = [$start_date,$end_date];

            $data =Penjualan::with(['detail_penjualan', 'detail_penjualan.stock', 'pembayaran', 'toko', 'tim', 'toko.ketentuan_toko', 'toko.ketentuan_toko.tim'])
            ->when(count($id_perusahaan) > 0, function ($q) use ($id_perusahaan) {
                return $q->whereIn('penjualan.id_perusahaan', $id_perusahaan);
            })
            ->when(count($id_depo) > 0, function ($q) use ($id_depo) {
                return $q->whereIn('penjualan.id_depo', $id_depo);  
            })
            ->when(count($id_salesman) >0, function ($q) use ($id_salesman) {
                return $q->whereIn('penjualan.id_salesman', $id_salesman);
            })
            ->when($id_toko <> '', function ($q) use ($id_toko){
                return $q->where('penjualan.id_toko', $id_toko);
            })
            ->when($keyword <> '', function ($q) use ($keyword){
                return $q->where('id',$keyword)
                        ->orWhereHas('toko', function ($q) use ($keyword){
                            return $q->where('id',$keyword)
                                   ->orWhere('nama_toko','like','%'.$keyword.'%')
                                   ->orWhere('no_acc','like','%'.$keyword.'%')
                                   ->orWhere('cust_no','like','%'.$keyword.'%');
                        });
            })
            ->where('penjualan.status', $status)
            ->where('penjualan.tipe_pembayaran','credit')
            ->whereDate('penjualan.tanggal','>=','2020-08-18')
            ->whereNull('penjualan.paid_at')
            //->whereBetween('penjualan.delivered_at', $date_filter)
            ->get(); 
            $data = PelunasanPenjualanResource::collection($data);
            if($id_toko == ''){
                $data = $data->groupBy('id_toko')->map(function ($row) {
                    return [
                        'data'               => Toko::find($row->first()->id_toko),
                        'id_toko'            => $row->first()->id_toko,
                        'jumlah_lunas'       => round($row->sum('jumlah_lunas')),
                        'jumlah_belum_bayar' => round($row->sum('jumlah_belum_bayar'))
                    ];
                });
            }
            $sorted = $data->sortByDesc('jumlah_belum_bayar')
                             ->where('jumlah_belum_bayar','>',5)
                             ->values()
                             ->all();
            return response()->json($sorted);
        else:
            return $this->Unauthorized();
        endif;
    }
}
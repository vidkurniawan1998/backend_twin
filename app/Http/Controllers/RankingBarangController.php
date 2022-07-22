<?php

namespace App\Http\Controllers;


use App\Helpers\Helper;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RankingBarangController extends Controller
{

    protected $jwt;
    public function __construct(JWTAuth $jwt){
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if ($this->user->can('Menu Laporan Ranking Barang')):
            $status         = 'delivered';
            $id_user        = $this->user->id;
            $end_date       = $request->end_date;
            $start_date     = $request->start_date;

            $id_perusahaan  = $request->has('id_perusahaan') && count($request->id_perusahaan)>0 ? $request->id_perusahaan : Helper::perusahaanByUser($id_user);
            $id_depo        = $request->has('depo')          && count($request->depo) > 0 ? $request->depo : Helper::depoIDByUser($id_user, $id_perusahaan);  
            $id_salesman    = $request->has('id_salesman')   && ($request->id_salesman!='all' && $request->id_salesman!='' && $request->id_salesman!=null) ?
                                  $request->id_salesman : '';
            $start_date     = $request->has('start_date')    && ($request->start_date!='0000-00-00' && $request->start_date!='' && $request->start_date!=null)
                                ? $request->start_date : Carbon::today()->toDateString();
            $end_date       = $request->has('end_date')      && ($request->end_date!='0000-00-00' && $request->end_date!='' && $request->end_date!=null) ?
                              $request->end_date : Carbon::today()->toDateString();
            $id_brand       = $request->has('id_brand')      && ($request->id_barang!='all' && $request->id_brand!='') ? $request->id_brand : [];
            $section        = $request->has('section')       &&  $request->section !='' ? $request->section : 'on';

            $start_date     = $start_date.' 00:00:00';
            $end_date       = $end_date. ' 23:59:59';
            $date_filter    = [$start_date,$end_date];

             $sub = DB::table('barang')
            ->join('segmen', 'barang.id_segmen', 'segmen.id')
            ->join('brand','brand.id','=','segmen.id_brand')
            ->join('stock', 'stock.id_barang', 'barang.id')
            ->join('detail_penjualan', 'detail_penjualan.id_stock', 'stock.id')
            ->join('penjualan', 'penjualan.id', 'detail_penjualan.id_penjualan')
            ->join('perusahaan', 'penjualan.id_perusahaan','perusahaan.id')
            ->join('depo','penjualan.id_depo','depo.id')
            ->whereBetween('penjualan.delivered_at', $date_filter)
            ->where('penjualan.status',$status)
            ->whereNull('penjualan.deleted_at')
            ->whereNull('barang.deleted_at')
            ->when(count($id_perusahaan) > 0, function ($q) use ($id_perusahaan) {
                return $q->whereIn('penjualan.id_perusahaan', $id_perusahaan);
            })
            ->when(count($id_depo) > 0, function ($q) use ($id_depo) {
                return $q->whereIn('penjualan.id_depo', $id_depo);  
            })
            ->when($id_salesman <> '', function ($q) use ($id_salesman) {
                return $q->where('penjualan.id_salesman', $id_salesman);
            })
            ->when(count($id_brand)> 0, function ($q) use ($id_brand){
                return $q->whereIn('brand.id',$id_brand);
            })
            ->select(
                'barang.id as id_barang',
                'depo.id   as id_depo',
                DB::raw('
                    SUM(
                        ((detail_penjualan.disc_rupiah/-1.1 * (detail_penjualan.qty + (detail_penjualan.qty_pcs/barang.isi)))     +
                        ((100-detail_penjualan.disc_persen)/100 * (detail_penjualan.qty + (detail_penjualan.qty_pcs/barang.isi)) *
                        (detail_penjualan.harga_jual/1.1))) * 1.1
                    ) as total,
                    SUM(detail_penjualan.qty+(detail_penjualan.qty_pcs/barang.isi)) as total_qty,
                    SUM(detail_penjualan.qty_pcs+(detail_penjualan.qty*barang.isi)) as total_qty_pcs
                '),
                'brand.nama_brand',
                'barang.nama_barang',
                'barang.berat',
                'barang.isi',
                'barang.kode_barang',
                'segmen.id_brand',
                'segmen.nama_segmen',
                'perusahaan.kode_perusahaan',
                'depo.nama_depo'
            );
            $sql_order = $sub->groupBy('barang.id');

            $list_order = DB::table(DB::raw("({$sql_order->toSql()}) as sub"))
            ->mergeBindings($sql_order)
            ->select(
                'sub.id_barang',
            )
            ->orderBy('sub.total','DESC')
            ->get();
            $order = [];
            foreach ($list_order as $row) {
                $order[] = $row->id_barang;
            }

            $sql_sub   = $sql_order->groupBy('depo.id');

            $rawOrder    = DB::raw(sprintf('FIELD(sub.id_barang, %s)', implode(',', $order)));
            $data = DB::table(DB::raw("({$sql_sub->toSql()}) as sub"))
            ->mergeBindings($sql_sub)
            ->when(count($order)>0, function ($q) use ($order){
                return $q->whereIn('sub.id_barang',$order);
            })
            ->when($section == 'on', function ($q) use ($section){
                return $q->orderBy('sub.id_brand','ASC');
            })
            ->when(count($order)>0, function ($q) use ($rawOrder){
                return $q->orderByRaw($rawOrder);
            })
            ->get();

            return response()->json($data);
        else:
            return $this->Unauthorized();
        endif;
    }
}

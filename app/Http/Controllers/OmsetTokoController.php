<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Http\Resources\OmsetToko as OmsetTokoResources;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;



class OmsetTokoController extends Controller
{

    protected $jwt;
    public function __construct(JWTAuth $jwt){
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if ($this->user->can('Menu Laporan Omset Toko')):
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
            $start_date     = $start_date.' 00:00:00';
            $end_date       = $end_date. ' 23:59:59';
            $date_filter    = [$start_date,$end_date];

            $sub = DB::table('toko')
            ->leftJoin('penjualan', 'penjualan.id_toko', 'toko.id')
            ->join('detail_penjualan', 'penjualan.id', 'detail_penjualan.id_penjualan')
            ->join('stock', 'detail_penjualan.id_stock', 'stock.id')
            ->join('gudang', 'stock.id_gudang', 'gudang.id')
            ->join('barang', 'stock.id_barang', 'barang.id')
            ->join('perusahaan', 'penjualan.id_perusahaan','perusahaan.id')
            ->join('depo','penjualan.id_depo','depo.id')
            ->where('penjualan.status', $status)
            ->whereNotNull('delivered_at')
            ->whereNull('penjualan.deleted_at')
            ->whereBetween('delivered_at', $date_filter)
            ->when(count($id_perusahaan) > 0, function ($q) use ($id_perusahaan) {
                return $q->whereIn('penjualan.id_perusahaan', $id_perusahaan);
            })
            ->when(count($id_depo) > 0, function ($q) use ($id_depo) {
                return $q->whereIn('penjualan.id_depo', $id_depo);  
            })
            ->when($id_salesman <> '', function ($q) use ($id_salesman) {
                return $q->where('penjualan.id_salesman', $id_salesman);
            })
            ->select(
                DB::raw('
                    SUM(
                        ((detail_penjualan.disc_rupiah/-1.1 * (detail_penjualan.qty + (detail_penjualan.qty_pcs/barang.isi)))     +
                        ((100-detail_penjualan.disc_persen)/100 * (detail_penjualan.qty + (detail_penjualan.qty_pcs/barang.isi)) *
                        (detail_penjualan.harga_jual/1.1))) * 1.1
                    ) as total,
                    count(penjualan.id) as banyak_penjualan
                '),
                'perusahaan.nama_perusahaan',
                'perusahaan.kode_perusahaan',
                'depo.nama_depo',
                'toko.nama_toko',
                'toko.no_acc',
                'toko.tipe',
                'toko.pemilik',
                'toko.id',
                )
            ->groupBy('toko.id');

            $data = DB::table(DB::raw("({$sub->toSql()}) as sub"))
            ->mergeBindings($sub)
            ->orderBy('sub.total','DESC')
            ->get();

            return response()->json($data);

        else:
            return $this->Unauthorized();
        endif;
    }

    public function detail(Request $request)
    {
        if ($this->user->can('Menu Laporan Omset Toko')):
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
            $id_active      = $request->has('id_active') ? $request->id_active : 0;
            $start_date     = $start_date.' 00:00:00';
            $end_date       = $end_date. ' 23:59:59';
            $date_filter    = [$start_date,$end_date];

            $sub = DB::table('penjualan')
            ->join('detail_penjualan', 'penjualan.id', 'detail_penjualan.id_penjualan')
            ->join('stock', 'detail_penjualan.id_stock', 'stock.id')
            ->join('gudang', 'stock.id_gudang', 'gudang.id')
            ->join('barang', 'stock.id_barang', 'barang.id')
            ->join('segmen', 'barang.id_segmen', 'segmen.id')
            ->join('brand','brand.id','=','segmen.id_brand')
            ->where('penjualan.status', $status)
            ->whereNotNull('delivered_at')
            ->whereNull('penjualan.deleted_at')
            ->whereBetween('delivered_at', $date_filter)
            ->when(count($id_perusahaan) > 0, function ($q) use ($id_perusahaan) {
                return $q->whereIn('penjualan.id_perusahaan', $id_perusahaan);
            })
            ->when(count($id_depo) > 0, function ($q) use ($id_depo) {
                return $q->whereIn('penjualan.id_depo', $id_depo);  
            })
            ->when($id_salesman <> '', function ($q) use ($id_salesman) {
                return $q->where('penjualan.id_salesman', $id_salesman);
            }) 
            ->where('penjualan.id_toko',$id_active)
            ->select(
                DB::raw('
                    SUM((detail_penjualan.qty+(detail_penjualan.qty_pcs/barang.isi))*detail_penjualan.harga_jual/1.1) as subtotal,
                    SUM(
                        (detail_penjualan.disc_rupiah/1.1 * (detail_penjualan.qty + (detail_penjualan.qty_pcs/barang.isi))) + 
                        (detail_penjualan.disc_persen/100 * (detail_penjualan.qty + (detail_penjualan.qty_pcs/barang.isi))  *
                        (detail_penjualan.harga_jual/1.1))
                    ) as discount,
                    SUM(detail_penjualan.qty+(detail_penjualan.qty_pcs/barang.isi)) as total_qty,
                    SUM(detail_penjualan.qty_pcs+(detail_penjualan.qty*barang.isi)) as total_qty_pcs
                '),
                'barang.nama_barang',
                'barang.kode_barang',
                'barang.berat',
                'barang.item_code',
                'barang.isi',
                'barang.id',
                'segmen.id_brand',
                'brand.nama_brand'
                )

            ->groupBy('barang.id');
            $data = DB::table(DB::raw("({$sub->toSql()}) as sub"))
            ->mergeBindings($sub)
            ->select(
                DB::raw('
                    *,
                    (subtotal-discount) as dpp,
                    ((subtotal-discount)*0.1) as ppn,
                    ((subtotal-discount)*1.1) as total
                ')
            )
            ->orderBy('sub.id_brand')
            ->orderBy('sub.subtotal','DESC')
            ->get();
            return response()->json($data);

        else:
            return $this->Unauthorized();
        endif;
    }
}

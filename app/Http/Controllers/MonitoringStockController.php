<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\User;
use App\Helpers\Helper;
use Carbon\Carbon;
use App\Models\Penjualan;
use App\Models\LogStock;
use App\Models\HariEfektif;
use Illuminate\Support\Facades\DB;

class MonitoringStockController extends Controller
{
    public function index(Request $request)
    {
        $tanggal_4  = $request->tanggal_4;
        $tanggal_8  = $request->tanggal_8;
        $tanggal_13 = $request->tanggal_13;
        $due_date   = $request->due_date;

        $date_filter_4  = [$tanggal_4.' 00:00:00',$due_date. ' 23:59:59'];
        $date_filter_8  = [$tanggal_8.' 00:00:00',$due_date. ' 23:59:59'];
        $date_filter_13 = [$tanggal_13.' 00:00:00',$due_date. ' 23:59:59'];

        DB::enableQueryLog(); 
        $stt_minggu_4 = Penjualan::join('detail_penjualan','detail_penjualan.id_penjualan','penjualan.id')
                        ->whereBetween('delivered_at',$date_filter_4)
                        ->where('status','delivered')
                        ->select( DB::raw(
                                    '4 as minggu,
                                    id_stock,
                                    detail_penjualan.qty as qty_detail,
                                    detail_penjualan.qty_pcs as qty_pcs_detail,
                                    harga_jual,
                                    disc_persen,
                                    disc_rupiah'));

        $stt_minggu_8 = Penjualan::join('detail_penjualan','detail_penjualan.id_penjualan','penjualan.id')
                        ->whereBetween('delivered_at',$date_filter_8)
                        ->where('status','delivered')
                        ->select( DB::raw(
                                    '8 as minggu,
                                    id_stock,
                                    detail_penjualan.qty as qty_detail,
                                    detail_penjualan.qty_pcs as qty_pcs_detail,
                                    harga_jual,
                                    disc_persen,
                                    disc_rupiah'));

        $stt_minggu_13 = Penjualan::join('detail_penjualan','detail_penjualan.id_penjualan','penjualan.id')
                        ->whereBetween('delivered_at',$date_filter_13)
                        ->where('status','delivered')
                        ->select( DB::raw(
                                    '13 as minggu,
                                    id_stock,
                                    detail_penjualan.qty as qty_detail,
                                    detail_penjualan.qty_pcs as qty_pcs_detail,
                                    harga_jual,
                                    disc_persen,
                                    disc_rupiah'));

        $data_stock = LogStock::join('gudang','log_stock.id_gudang','gudang.id')
        ->whereDate('tanggal','<=',$due_date)
        ->where(function($q){
            return $q->orWhere(function($q){
                return $q->where('referensi','stock awal');
             })
            ->orWhere(function($q){
                return $q->where('referensi','penerimaan barang');
             })
            ->orWhere(function($q){
                return $q->where('referensi','mutasi masuk')->where('status','received');
             })
            ->orWhere(function($q){
                return $q->where('referensi','penjualan')->where('status','delivered');
             })
            ->orWhere(function($q){
                return $q->where('referensi','mutasi keluar')->where('status','received');
             })        
            ->orWhere(function($q){
                return $q->where('referensi','adjustment');
             })
            ->orWhere(function($q){
                return $q->where('referensi','retur');
             });
        })
        ->where('gudang.jenis','baik')
        ->select(
            DB::raw('id_barang, id_depo, SUM(qty_pcs*parameter) as savl ')
        )
        ->groupBy('id_barang')
        ->groupBy('id_depo');

        $data_merge = $stt_minggu_4->unionAll($stt_minggu_8)->unionAll($stt_minggu_13);
        $data_sum = DB::table($data_merge)
        // QTY barang dengan DPP 0 tidak dimasukan dala perhitungan
        ->whereRaw(
        '((qty_detail+(qty_pcs_detail/barang.isi)) / 1.1 *((harga_jual * (100 - disc_persen) / 100) - disc_rupiah)) > 0'
        )
        ->select(DB::raw(
                'barang.id as id_barang,
                depo.id as id_depo,
                barang.nama_barang,
                CONCAT(depo.nama_depo," ",perusahaan.kode_perusahaan) AS nama_depo,
                nama_segmen,
                nama_brand,
                nama_principal,
                barang.isi,
                kode_barang,
                minggu,
                SUM(
                    (qty_detail + (qty_pcs_detail/barang.isi)) / minggu
                ) as total'))
        ->join('stock','stock.id','id_stock')
        ->join('barang','barang.id','stock.id_barang')
        ->join('segmen','segmen.id','barang.id_segmen')
        ->join('brand','brand.id','segmen.id_brand')
        ->join('principal','principal.id','brand.id_principal')
        ->join('gudang','gudang.id','stock.id_gudang')
        ->join('depo','depo.id','gudang.id_depo')
        ->join('perusahaan','perusahaan.id','depo.id_perusahaan')
        ->groupBy('barang.id')
        ->groupBy('depo.id')
        ->groupBy('minggu');

        $data_res = DB::table(DB::raw("({$data_sum->toSql()}) as data_sum"))
        ->mergeBindings($data_sum)
        ->select(
            DB::raw('
                data_sum.id_barang, 
                data_sum.id_depo, 
                nama_principal,
                nama_brand,
                nama_segmen,
                kode_barang,
                nama_barang, 
                nama_depo, 
                MAX(total) as stt, 
                (savl/data_sum.isi) as savl')
        )
        ->joinSub($data_stock,'data_stock', function($join){
            $join->on('data_stock.id_barang','data_sum.id_barang')->on('data_stock.id_depo','data_sum.id_depo');
        })
        ->groupBy('data_sum.id_barang')
        ->groupBy('data_sum.id_depo')
        ->orderBy('data_sum.id_depo')
        ->orderBy('data_sum.id_barang')
        //->limit(15)
        ->get();

        //dd(DB::getQueryLog());
        return response()->json($data_res);
    }

    public function hari_efektif(Request $request)
    {
        $tanggal        = $request->tanggal;
        $tanggalEx      = explode('-',$tanggal);  //tanggal Explode
        $tanggalSt      = $tanggalEx[0].'-01-01'; //tanggal start
        $today          = HariEfektif::where('tanggal',$tanggal)->first();

        $tanggal_minggu_4   = $today->minggu-4+1;
        $tanggal_minggu_8   = $today->minggu-8+1;
        $tanggal_minggu_13  = $today->minggu-13+1;

        $minggu_4        = HariEfektif::whereBetween('tanggal',[$tanggalSt,$tanggal])->where('minggu',$tanggal_minggu_4)->first();
        $minggu_8        = HariEfektif::whereBetween('tanggal',[$tanggalSt,$tanggal])->where('minggu',$tanggal_minggu_8)->first();
        $minggu_13       = HariEfektif::whereBetween('tanggal',[$tanggalSt,$tanggal])->where('minggu',$tanggal_minggu_13)->first();

        return response()->json([
            'minggu_1'   => $today->minggu,
            'minggu_4'   => $minggu_4->minggu,
            'minggu_8'   => $minggu_8->minggu,
            'minggu_13'  => $minggu_13->minggu,
            'tanggal_1'  => $today->tanggal,
            'tanggal_4'  => $minggu_4->tanggal,
            'tanggal_8'  => $minggu_8->tanggal,
            'tanggal_13' => $minggu_13->tanggal,
        ]);

    }
}

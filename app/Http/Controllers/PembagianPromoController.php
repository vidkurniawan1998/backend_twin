<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Helpers\Helper;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Penjualan;
use App\Http\Resources\PembagianPromo as PembagianPromoResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class PembagianPromoController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if (!$this->user->can('Menu Laporan Pembagian Promo')){
            return $this->Unauthorized();
        }
        $id_user            = $this->user->id;
        $id_perusahaan      = $request->has('id_perusahaan') && $request->id_perusahaan <> '' ? [$request->id_perusahaan] : Helper::perusahaanByUser($id_user);
        $id_principal       = $request->has('id_principal') ? $request->id_principal : Helper::principalByUser($id_user);
        $tipe_promo         = $request->has('tipe_promo') ? $request->tipe_promo : [];
        $start_date         = $request->start_date;
        $end_date           = $request->end_date;
        $start_date         = $start_date.' 00:00:00';
        $end_date           = $end_date. ' 23:59:59';
        $date_filter        = [$start_date,$end_date];
        //DB::enableQueryLog();

        $data = Penjualan::join('detail_penjualan','detail_penjualan.id_penjualan','penjualan.id')
        ->join('depo','penjualan.id_depo','depo.id')
        ->join('perusahaan','penjualan.id_perusahaan','perusahaan.id')
        ->join('stock','detail_penjualan.id_stock','stock.id')
        ->join('barang','barang.id','stock.id_barang')
        ->join('promo','detail_penjualan.id_promo','promo.id')
        ->where('detail_penjualan.id_promo','>',0)
        ->whereBetween('penjualan.delivered_at',$date_filter)
        ->when(count($id_perusahaan)>0, function ($q) use ($id_perusahaan){
            return $q->whereIn('barang.id_perusahaan',$id_perusahaan);
        })
        ->when(count($id_principal)>0, function ($q) use ($id_principal){
            return $q->join('segmen','segmen.id','barang.id_segmen')
                     ->join('brand','brand.id','segmen.id_brand')
                     ->whereIn('brand.id_principal',$id_principal);
        })
        ->when(count($tipe_promo)>0, function ($q) use ($tipe_promo){
            return $q->whereIn('promo.tipe_promo',$tipe_promo);
        })
        ->select(
            DB::raw('
                0 as extra,
                barang.id, detail_penjualan.id_promo,
                nama_depo,
                nama_perusahaan,
                no_promo,
                nama_barang,
                kode_barang,
                nama_promo,
                SUM(detail_penjualan.qty+((detail_penjualan.qty_pcs)/isi)) as qty,
                SUM((detail_penjualan.qty+((detail_penjualan.qty_pcs)/isi))*detail_penjualan.harga_jual/1.1) as subtotal,
                SUM(detail_penjualan.disc_rupiah)/count(detail_penjualan.id)/1.1 as disc_rupiah,
                promo.disc_rupiah_distributor/1.1 as disc_rupiah_distributor,
                promo.disc_rupiah_principal/1.1 as disc_rupiah_principal,
                promo.disc_1,
                promo.disc_2,
                promo.disc_3,
                promo.disc_4,
                promo.disc_5,
                promo.disc_6
            ')
        )
        ->groupBy('penjualan.id_depo')
        ->groupBy('stock.id_barang')
        ->groupBy('detail_penjualan.id_promo');
        //->get();

        $data_extra = Penjualan::join('detail_penjualan','detail_penjualan.id_penjualan','penjualan.id')
        ->join('depo','penjualan.id_depo','depo.id')
        ->join('perusahaan','penjualan.id_perusahaan','perusahaan.id')
        ->join('stock','detail_penjualan.id_stock','stock.id')
        ->join('barang','barang.id','stock.id_barang')
        ->whereBetween('penjualan.delivered_at',$date_filter)
        ->where('harga_jual',0)
        ->when(count($id_perusahaan)>0, function ($q) use ($id_perusahaan){
            return $q->whereIn('penjualan.id_perusahaan',$id_perusahaan);
        })
        ->when(count($id_principal)>0, function ($q) use ($id_principal){
            return $q->join('segmen','segmen.id','barang.id_segmen')
                     ->join('brand','brand.id','segmen.id_brand')
                     ->whereIn('brand.id_principal',$id_principal);
        })
        ->select(
            DB::raw('
                1 as extra,
                barang.id, 0 as id_promo,
                nama_depo,
                nama_perusahaan,
                "" as no_promo,
                nama_barang,
                kode_barang,
                "" as nama_promo,
                SUM(detail_penjualan.qty+((detail_penjualan.qty_pcs)/isi)) as qty,
                SUM((detail_penjualan.qty+((detail_penjualan.qty_pcs)/isi))*detail_penjualan.harga_dbp/1.1) as subtotal,
                SUM(detail_penjualan.disc_rupiah)/count(detail_penjualan.id)/1.1 as disc_rupiah,
                0 as disc_rupiah_distributor,
                0 as disc_rupiah_principal,
                100 as disc_1,
                0 as disc_2,
                0 as disc_3,
                0 as disc_4,
                0 as disc_5,
                0 as disc_6
            ')
        )
        ->groupBy('penjualan.id_depo')
        ->groupBy('stock.id_barang');
        //->get();

        //cara lain
        //$merged = $data->merge($data_extra);

        $merged = $data->unionAll($data_extra)->get();
        //dd(DB::getQueryLog());
        return PembagianPromoResource::collection($merged);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Stock;
use App\Models\PosisiStock;
// use App\Http\Resources\Canvass as CanvassResource;

class TestController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    // public function test(Request $request)
    // {
    //     //get stock, loop stock
    //     //get posisi stock h-1
    //     //new posisi stock->saldo awal = posisi_stock_kemarin->saldo_akhir


    //     // $today = \Carbon\Carbon::today()->toDateString();
    //     // $yesterday = \Carbon\Carbon::yesterday()->toDateString();
    //     $today = '2019-05-19';
    //     $yesterday = '2019-05-18';

    //     $stock = Stock::with('barang')->get();

    //     foreach($stock as $s){
            
    //         $posisi_stock_kemarin = PosisiStock::where('id_stock', $s->id)->where('tanggal', $yesterday)->first();
    //         if($posisi_stock_kemarin){
    //             $saldo_awal_qty = $posisi_stock_kemarin->saldo_akhir_qty;
    //             $saldo_awal_pcs = $posisi_stock_kemarin->saldo_akhir_pcs;
    //         }

    //         $posisi_stock = new PosisiStock;
    //         $posisi_stock->id_stock = $s->id;
    //         $posisi_stock->tanggal = $today;
    //         $posisi_stock->saldo_awal_qty = $saldo_awal_qty;
    //         $posisi_stock->saldo_awal_pcs = $saldo_awal_pcs;
    //         //pembelian
    //             // detail_penerimaan where id_stock->get(), where tgl_bongkar = yesterday, where is_approved = 1;
    //             // if($detail_penerimaan){
    //                 // $posisi_stock->pembelian_qty = $detail_penerimaan->sum('qty');
    //                 // $posisi_stock->pembelian_pcs = $detail_penerimaan->sum('qty_pcs');
    //             // }
    //         //mutasi_masuk
    //         //penjualan
    //         //mutasi_keluar
    //         $posisi_stock->saldo_akhir_qty = $s->qty;
    //         $posisi_stock->saldo_akhir_pcs = $s->qty_pcs;
    //         $posisi_stock->harga = $s->dbp;
    //         $posisi_stock->nilai_stock = $s->dbp * ($s->qty + ($s->qty_pcs / $s->barang->isi));
    //         $posisi_stock->save();
    //     }

    //     return 'generate posisi stock berhasil';
    // }

    // public function test1(Request $request)
    // {
    //     // $today = \Carbon\Carbon::today()->toDateString();
    //     $today = '2019-05-18';

    //     $stock = Stock::with('barang')->get();

    //     foreach($stock as $s){
    //         $posisi_stock = new PosisiStock;
    //         $posisi_stock->id_stock = $s->id;
    //         $posisi_stock->tanggal = $today;
    //         $posisi_stock->saldo_akhir_qty = $s->qty;
    //         $posisi_stock->saldo_akhir_pcs = $s->qty_pcs;
    //         $posisi_stock->harga = $s->dbp;
    //         $posisi_stock->nilai_stock = $s->dbp * ($s->qty + ($s->qty_pcs / $s->barang->isi));
    //         $posisi_stock->save();
    //     }

    //     return 'generate posisi stock berhasil';
    // }
    
    public function test(Request $request) {

        // $today = \Carbon\Carbon::today()->toDateString();
        $today = '2020-05-06';

        $id_stock = Stock::pluck('id');

        foreach($id_stock as $is){
            $stock = Stock::find($is);

            $posisi_stock = new PosisiStock;
            $posisi_stock->id_stock = $is;
            $posisi_stock->tanggal = $today;
            $posisi_stock->harga = $stock->dbp;

            $posisi_stock->saldo_awal_qty = $stock->qty;
            $posisi_stock->saldo_awal_pcs = $stock->qty_pcs;
            
            $posisi_stock->saldo_akhir_qty = $stock->qty;
            $posisi_stock->saldo_akhir_pcs = $stock->qty_pcs;

            $posisi_stock->save();
        }

        // $this->info('Laporan posisi stock harian (tanggal ' . $today . ') sudah berhasil dibuat. ');
        return 'Laporan posisi stock harian (tanggal ' . $today . ') sudah berhasil dibuat. ';
    }

}
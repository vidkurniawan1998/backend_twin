<?php

use Illuminate\Database\Seeder;
use illuminate\Support\Facades\DB;

use App\Models\PosisiStock;
use App\Models\Stock;

class PosisiStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $today      = \Carbon\Carbon::today()->toDateString();
        $posisi     = PosisiStock::whereDate('tanggal', $today)->pluck('id_stock');
        $id_stock   = Stock::whereNotIn('id', $posisi)->pluck('id');

        foreach($id_stock as $is){
            $stock = Stock::find($is);
            $posisi_stock = new PosisiStock;
            $posisi_stock->id_stock = $is;
            $posisi_stock->tanggal  = $today;
            $posisi_stock->harga    = $stock->dbp;

            $posisi_stock->saldo_awal_qty = $stock->qty;
            $posisi_stock->saldo_awal_pcs = $stock->qty_pcs;
            
            $posisi_stock->saldo_akhir_qty = $stock->qty;
            $posisi_stock->saldo_akhir_pcs = $stock->qty_pcs;

            $posisi_stock->save();
        }
    }
}

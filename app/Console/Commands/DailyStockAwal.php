<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use illuminate\Support\Facades\DB;

use App\Models\Stock;
use App\Models\StockAwal;

class DailyStockAwal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily:stock_awal';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update data stock dan penjualan agar sesuai, error saat approve dll';
    /**
     * Create a new command instance.
     *
     * @return void
     */

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // script cron job dengan asumsi dilakukan setiap hari jam 01:00
        $today      = \Carbon\Carbon::today()->toDateString();
        $stock_awal = StockAwal::whereDate('tanggal', $today)->pluck('id_stock');
        $id_stock   = Stock::whereNotIn('id', $stock_awal)->pluck('id');

        // hitung sales pending dengan tanggal approve kemarin
        $yesterday = date_add(date_create($today), date_interval_create_from_date_string("-1 days"));
        $sales_pending = DB::table('penjualan AS a')
            ->join('detail_penjualan AS b', 'a.id', '=', 'b.id_penjualan')
            ->join('stock AS c', 'b.id_stock', '=', 'c.id')
            ->whereDate('a.approved_at', '<=', $yesterday)
            ->whereBetween('a.tanggal', ['2020-08-18', $yesterday])
            ->where('a.status', '=', 'approved')
            ->whereNull('a.deleted_at')
            ->select('c.id', DB::raw('SUM(b.qty) AS qty'), DB::raw('SUM(b.qty_pcs) AS qty_pcs '))
            ->groupBy('c.id')->get();

        // hitung mutasi pending
        $mutasi_pending = DB::table('detail_mutasi_barang AS b')
            ->join('mutasi_barang AS a', 'a.id', 'b.id_mutasi_barang')
            ->join('stock AS c', 'b.id_stock', '=', 'c.id')
            ->whereBetween('a.tanggal_mutasi', ['2020-08-18', $yesterday])
            ->where('a.is_approved', 1)
            ->where('a.status', 'approved')
            ->whereNull('a.deleted_at')
            ->select('c.id', DB::raw('SUM(b.qty) AS qty'), DB::raw('SUM(b.qty_pcs) AS qty_pcs '))
            ->groupBy('c.id')->get();

        // simpan qty dari table stock dan qty sales pending dengan tanggal approve kemarin ke tabel stock awal
        foreach ($id_stock as $is) {
            $qty_pending = 0;
            $qty_pcs_pending = 0;

            $qty_mutasi_pending = 0;
            $qty_pcs_mutasi_pending = 0;

            if (!$sales_pending->isEmpty()) {
                $coll = $sales_pending->where('id', $is);
                if (!$coll->isEmpty()) {
                    $qty_pending += $coll->first()->qty;
                    $qty_pcs_pending += $coll->first()->qty_pcs;
                }
            }

            if (!$mutasi_pending->isEmpty()) {
                $coll = $mutasi_pending->where('id', $is);
                if (!$coll->isEmpty()) {
                    $qty_mutasi_pending += $coll->first()->qty;
                    $qty_pcs_mutasi_pending += $coll->first()->qty_pcs;
                }
            }

            $stock = Stock::find($is);
            $stock_awal = new StockAwal;
            $stock_awal->id_stock = $is;
            $stock_awal->tanggal  = $today;
            $stock_awal->qty_stock = $stock->qty;
            $stock_awal->qty_pcs_stock = $stock->qty_pcs;
            $stock_awal->qty_pending = $qty_pending;
            $stock_awal->qty_pcs_pending = $qty_pcs_pending;
            $stock_awal->qty_mutasi_pending = $qty_mutasi_pending;
            $stock_awal->qty_pcs_mutasi_pending = $qty_pcs_mutasi_pending;
            $stock_awal->harga = $stock->dbp;

            $stock_awal->save();
        }
    }
}

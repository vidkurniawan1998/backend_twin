<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
// use Illuminate\Database\Eloquent\Model; //model

use App\Models\PosisiStock;
use App\Models\Stock;
use App\Models\HargaBarang;

class DailyStockReport extends Command
{
   /**
    * The name and signature of the console command.
    *
    * @var string
    */
   protected $signature = 'daily:stock_report';
   /**
    * The console command description.
    *
    * @var string
    */
   protected $description = 'Membuat data laporan posisi stock harian yang dijalankan setiap closing penjualan (misalnya jam 11 malam)';
   /**
    * Create a new command instance.
    *
    * @return void
    */
   public function __construct()
   {
       parent::__construct();
   }
   /**
    * Execute the console command.
    *
    * @return mixed
    */
   public function handle()
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

        $this->info('Laporan posisi stock harian (tanggal ' . $today . ') sudah berhasil dibuat. ');
   }
}
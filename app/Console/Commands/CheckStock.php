<?php


namespace App\Console\Commands;


use App\Models\DetailPenjualan;
use App\Models\PosisiStock;
use App\Models\Stock;
use App\Models\DetailReturPenjualan;
use App\Models\ViewAdjusmentBarang;
use App\Models\ViewMutasiKeluar;
use App\Models\ViewMutasiMasuk;
use App\Models\ViewPenerimaanBarang;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily:check_stock';
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
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // $latestOpname   = Carbon::now()->toDateString();
        $latestOpname   = '2020-09-18';
        $firstPoDate    = '2020-09-14';
        // $stock          = Stock::whereIn('id_gudang', [12, 13, 15])->get();
        $stock          = Stock::whereIn('id_gudang', [19])->get();
        $akhir = [];
        foreach($stock as $stk){
            $pos            = PosisiStock::whereDate('tanggal', $latestOpname)->where('id_stock', $stk->id)->first();
            if (!$pos) {
                continue;
            }
            $qtyAkhir       = 0;
            $pcsAkhir       = 0;
            $qtyAkhir+= $pos->saldo_awal_qty;
            $pcsAkhir+= $pos->saldo_awal_pcs;

            // PENJUALAN
            $penjualan      = DetailPenjualan::with('penjualan')->where('id_stock', $stk->id)
                                ->whereHas('penjualan', function ($q) use ($firstPoDate) {
                                    $q->whereDate('tanggal', '>=', $firstPoDate)
                                    ->where('no_invoice', 'not like', '%140920%')
                                    ->whereIn('status', ['approved', 'loaded', 'delivered']);
                                })->get();

            $qtyAkhir-= $penjualan->sum('qty');
            $pcsAkhir-= $penjualan->sum('qty_pcs');
            
            // RETUR BAIK
            $rb = DetailReturPenjualan::with('retur_penjualan')->where('id_barang', $stk->id_barang)
                ->whereHas('retur_penjualan', function($q) use ($latestOpname, $stk) {
                    $q->whereDate('approved_at', '>=', $latestOpname)
                    ->where('id_gudang', $stk->id_gudang)
                    ->where('status', 'approved');
                })->get();
            $qtyAkhir+= $rb->sum('qty_dus');
            $pcsAkhir+= $rb->sum('qty_pcs');

            //PENERIMAAN BARANG
            $pb = ViewPenerimaanBarang::where('id_gudang', $stk->id_gudang)->where('id_barang', $stk->id_barang)->where('status', 1)->whereDate('tanggal', '>=', $latestOpname)->get();
            $qtyAkhir+= $pb->sum('qty');
            $pcsAkhir+= $pb->sum('qty_pcs');

            //MUTASI MASUK
            $mm = ViewMutasiMasuk::where('id_gudang', $stk->id_gudang)->where('id_barang', $stk->id_barang)->where('status', 'received')->whereDate('tanggal', '>=', $latestOpname)->get();
            $qtyAkhir+= $mm->sum('qty');
            $pcsAkhir+= $mm->sum('qty_pcs');

            //MUTASI KELUAR
            $mk = ViewMutasiKeluar::where('id_gudang', $stk->id_gudang)->where('id_barang', $stk->id_barang)->whereIn('status', ['approved', 'received'])->whereDate('tanggal', '>=', $latestOpname)->get();
            $qtyAkhir-= $mk->sum('qty');
            $pcsAkhir-= $mk->sum('qty_pcs');

            //ADJUSTMENT
            $adj = ViewAdjusmentBarang::where('id_gudang', $stk->id_gudang)->where('id_barang', $stk->id_barang)->where('status', 'approved')->whereDate('tanggal', '>=', $latestOpname)->get();
            $qtyAkhir+= $adj->sum('qty');
            $pcsAkhir+= $adj->sum('qty_pcs');

            $inPcs  = ($qtyAkhir * $stk->isi) + $pcsAkhir;
            $pcs    = $inPcs % $stk->isi;
            $qty    = ($inPcs - $pcs) / $stk->isi;

            // $pos->penjualan_qty = $penjualan->sum('qty');
            // $pos->penjualan_pcs = $penjualan->sum('qty_pcs');
            // $pos->saldo_akhir_qty = $qty;
            // $pos->saldo_akhir_pcs = $pcs;
            // $pos->save();
            // $stk->qty       = $qty;
            // $stk->qty_pcs   = $pcs;
            // $stk->save();
            $inPcsStock  = ($stk->qty * $stk->isi) + $stk->qty_pcs;
            $kode_barang = $stk->kode_barang;
            $this->info($stk->gudang->nama_gudang." ".$kode_barang.' :  '."{$inPcs} <=> {$inPcsStock}");
            // echo "\n"; 
            if($inPcs <> $inPcsStock) {
                $this->info($stk->gudang->nama_gudang." ".$kode_barang.' :  '."{$qty}/{$pcs}");
                echo "Selisih bos \n";        
            }
        
        
        }
    }
}
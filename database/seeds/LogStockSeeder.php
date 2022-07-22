<?php

use Illuminate\Database\Seeder;
use illuminate\Support\Facades\DB;
use App\Models\Penjualan;
use App\Models\Stock;
use App\Models\LogStock;
use App\Models\Adjustment;
use App\Models\PenerimaanBarang;
use App\Models\ReturPenjualan;
use \Carbon\Carbon as Carbon;

class LogStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->log_stock_penjualan();
        // $this->log_stock_adjustment();
        // $this->log_stock_penerimaan();
        // $this->log_stock_retur();
    }

    //Log Stock Penjualan
    public function log_stock_penjualan()
    {
        $detail_penjualan = Penjualan::select(DB::raw('
                            penjualan.*,
                            detail_penjualan.*,
                            stock.id_barang,
                            stock.id_gudang as id_gudang,
                            barang.isi as isi
                        '))
                        ->join('detail_penjualan','penjualan.id','detail_penjualan.id_penjualan')
                        ->join('stock','detail_penjualan.id_stock','stock.id')
                        ->join('barang','stock.id_barang','barang.id')
                        ->whereDate('tanggal_invoice', '>=', '2020-12-26')
                        ->whereDate('tanggal_invoice', '<=', '2020-12-28')
                        ->whereIn('penjualan.id_gudang', [12, 13, 14, 15, 16, 28])
                        ->whereNull('penjualan.deleted_at')
                        ->orderBy('penjualan.id', 'asc')
                        ->get();

        $logStock = [];
        foreach ($detail_penjualan as $row) {
            if ($row->status != 'waiting') {
                // if ($row->tanggal) {
                //      $logStock[] = [
                //         'tanggal'       => $row->tanggal_invoice,
                //         'id_barang'     => $row->id_barang,
                //         'id_gudang'     => $row->id_gudang,
                //         'id_user'       => $row->approved_by != NULL ? $row->approved_by : 1,
                //         'id_referensi'  => $row->id,
                //         'referensi'     => 'penjualan',
                //         'no_referensi'  => $row->id_penjualan,
                //         'qty_pcs'       => ($row->qty * $row->isi) + $row->qty_pcs,
                //         'status'        => 'approved',
                //         'created_at'    => $row->created_at,
                //         'updated_at'    => $row->updated_at
                //     ];
                // }
                // if ($row->tanggal_jadwal) {
                //   $logStock[] = [
                //         'tanggal'       => $row->tanggal_jadwal,
                //         'id_barang'     => $row->id_barang,
                //         'id_gudang'     => $row->id_gudang,
                //         'id_user'       => $row->loading_by != 0 ? $row->loading_by : 1,
                //         'id_referensi'  => $row->id,
                //         'referensi'     => 'penjualan',
                //         'no_referensi'  => $row->id_penjualan,
                //         'qty_pcs'       => ($row->qty * $row->isi) + $row->qty_pcs,
                //         'status'        => 'loaded',
                //         'created_at'    => $row->created_at,
                //         'updated_at'    => $row->updated_at
                //     ];
                // }
                if ($row->delivered_at) {
                    $logStock[] = [
                        'tanggal'       => Carbon::parse($row->delivered_at)->toDateString(),
                        'id_barang'     => $row->id_barang,
                        'id_gudang'     => $row->id_gudang,
                        'id_user'       => $row->delivered_by != NULL ? $row->delivered_by : 1,
                        'id_referensi'  => $row->id,
                        'referensi'     => 'penjualan',
                        'no_referensi'  => $row->id_penjualan,
                        'qty_pcs'       => ($row->qty * $row->isi) + $row->qty_pcs,
                        'status'        => 'delivered',
                        'created_at'    => $row->created_at,
                        'updated_at'    => $row->updated_at
                    ];
                }
            }
        }
        $collection = collect($logStock);
        $chunk = $collection->chunk(1000)->toArray();
        foreach ($chunk as $data) {
            LogStock::insert($data);
        }
        echo "Sukses Insert Log Penjualan \n";
    }
    //End Log Stock Penjualan

    //Log Stock Adjusment
    public function log_stock_adjustment()
    {
        $detail_adjustment = Adjustment::join('detail_adjustment','adjustment.id','detail_adjustment.id_adjustment')
                        // ->where('adjustment.id',278)
                        ->whereDate('tanggal', '>=', '2020-09-18')
                        ->whereNull('adjustment.deleted_at')
                        ->orderBy('adjustment.id', 'asc')
                        ->get();

        foreach ($detail_adjustment as $row) {
            if ($row->status == 'approved') {
                $hasil = $this->create_log_adjustment($row,'approved',$row->tanggal);
            }
        }
        echo "Sukses Insert Log Adjustment \n";
    }

    public function create_log_adjustment($da,$status,$tanggal)
    {
        $stock = Stock::withTrashed()->find($da->id_stock);
        $logStock = [
            'tanggal'       => $tanggal,
            'id_barang'     => $stock->id_barang,
            'id_gudang'     => $stock->id_gudang,
            'id_user'       => $da->created_by != NULL ? $da->created_by : 1,
            'id_referensi'  => $da->id,
            'referensi'     => 'adjustment',
            'no_referensi'  => $da->id_adjustment,
            'qty_pcs'       => ($da->qty_adj * $stock->isi) + $da->pcs_adj,
            'status'        => $status,
            'created_at'    => $da->created_at,
            'updated_at'    => $da->updated_at
        ];

        LogStock::create($logStock);
    }
    //End Log Stock Adjustment

    //Log Stock Penerimaan
    public function log_stock_penerimaan()
    {
        $detail_penerimaan = PenerimaanBarang::join('detail_penerimaan_barang','penerimaan_barang.id','detail_penerimaan_barang.id_penerimaan_barang')
                        // ->where('penerimaan_barang.id',420)
                        ->whereDate('tgl_bongkar', '>=', '2020-09-18')
                        ->whereNull('penerimaan_barang.deleted_at')
                        ->whereNull('detail_penerimaan_barang.deleted_at')
                        ->orderBy('penerimaan_barang.id', 'asc')
                        ->get();

        foreach ($detail_penerimaan as $row) {
            if ($row->is_approved == 1) {
                $hasil = $this->create_log_penerimaan($row,'approved',$row->tgl_bongkar);
            }
        }
        echo "Sukses Insert Log Penerimaam \n";
    }

    public function create_log_penerimaan($dpb,$status,$tanggal)
    {
        $stock = Stock::withTrashed()->where('id_gudang', $dpb->id_gudang)->where('id_barang', $dpb->id_barang)->first();        
        $logStock = [
            'tanggal'       => $tanggal,
            'id_barang'     => $stock->id_barang,
            'id_gudang'     => $stock->id_gudang,
            'id_user'       => $dpb->created_by != NULL ? $dpb->created_by : 1,
            'id_referensi'  => $dpb->id,
            'referensi'     => 'penerimaan barang',
            'no_referensi'  => $dpb->id_penerimaan_barang,
            'qty_pcs'       => ($dpb->qty * $stock->isi) + $dpb->qty_pcs,
            'status'        => $status,
            'created_at'    => $dpb->created_at,
            'updated_at'    => $dpb->updated_at
        ];

        LogStock::create($logStock);
    }
    //End Log Stock Penerimaan

    //Log Stock Retur Penjualan
    public function log_stock_retur()
    {
        $detail_penerimaan = ReturPenjualan::join('detail_retur_penjualan','retur_penjualan.id','detail_retur_penjualan.id_retur_penjualan')
                        ->whereDate('approved_at', '>=', '2020-09-18')
                        ->whereNull('retur_penjualan.deleted_at')
                        ->orderBy('retur_penjualan.id', 'asc')
                        ->get();

        foreach ($detail_penerimaan as $row) {
            if ($row->status == 'approved') {
                $hasil = $this->create_log_retur($row,'approved',$row->approved_at);
            }
        }
        echo "Sukses Insert Log Retur Penjualan \n";
    }

    public function create_log_retur($drp,$status,$tanggal)
    {
        $stock = Stock::withTrashed()->where('id_gudang', $drp->id_gudang)->where('id_barang', $drp->id_barang)->first();    
        $logStock = [
            'tanggal'       => $tanggal,
            'id_barang'     => $stock->id_barang,
            'id_gudang'     => $stock->id_gudang,
            'id_user'       => $drp->approved_by != NULL ? $drp->approved_by : 1,
            'id_referensi'  => $drp->id,
            'referensi'     => 'retur',
            'no_referensi'  => $drp->id_retur_penjualan,
            'qty_pcs'       => ($drp->qty_dus * $stock->isi) + $drp->qty_pcs,
            'status'        => $status,
            'created_at'    => $drp->created_at,
            'updated_at'    => $drp->updated_at
        ];

        LogStock::create($logStock);
    }
    //End Log Stock Retur Penjualan
}

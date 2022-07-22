<?php

use App\Models\Stock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\LogStock;

class LogStockAwalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $id_stock = Stock::where('id_gudang', 28)->get()->pluck('id');
        $posisi_stock = DB::table('posisi_stock')->where('tanggal', '2020-11-17')->whereIn('id_stock', $id_stock)
            ->select(
                'posisi_stock.id',
                'posisi_stock.id_stock',
                'posisi_stock.tanggal',
                'posisi_stock.saldo_awal_qty',
                'posisi_stock.saldo_awal_pcs',
                'posisi_stock.created_at',
                'posisi_stock.updated_at'
            )
            ->get();

        $logStock = [];
        foreach ($posisi_stock as $ps) {
            $stock = Stock::join('barang', 'stock.id_barang', 'barang.id')
                ->where('stock.id', $ps->id_stock)
                ->select('stock.id_barang', 'stock.id_gudang', 'barang.isi')
                ->first();

            $logStock[] = [
                'tanggal'       => '2020-11-16',
                'id_barang'     => $stock->id_barang,
                'id_gudang'     => $stock->id_gudang,
                'id_user'       => 130,
                'id_referensi'  => $ps->id,
                'referensi'     => 'stock awal',
                'no_referensi'  => 1,
                'qty_pcs'       => ($ps->saldo_awal_qty * $stock->isi) + $ps->saldo_awal_pcs,
                'status'        => 'approved',
                'created_at'    => $ps->created_at,
                'updated_at'    => $ps->updated_at
            ];
        }

        $chunk_data = array_chunk($logStock, 1000);
        if (isset($chunk_data) && !empty($chunk_data)) {
            foreach ($chunk_data as $chunk_data_val) {
                LogStock::insert($chunk_data_val);
            }
        }
    }
}

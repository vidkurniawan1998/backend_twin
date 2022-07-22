<?php

use Illuminate\Database\Seeder;
use App\Models\MutasiBarang;
use App\Models\LogStock;
use App\Models\Stock;

class MutasiLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $detail_mutasi = MutasiBarang::join('detail_mutasi_barang', 'mutasi_barang.id', 'detail_mutasi_barang.id_mutasi_barang')
            ->join('stock', 'detail_mutasi_barang.id_stock', 'stock.id')
            ->join('barang', 'stock.id_barang', 'barang.id')
            ->select(
                'detail_mutasi_barang.id_mutasi_barang',
                'detail_mutasi_barang.id',
                'mutasi_barang.tanggal_mutasi',
                'mutasi_barang.tanggal_realisasi',
                'mutasi_barang.ke',
                'stock.id_barang',
                'stock.id_gudang',
                'detail_mutasi_barang.qty',
                'detail_mutasi_barang.qty_pcs',
                'barang.isi',
                'mutasi_barang.created_at',
                'mutasi_barang.updated_at',
                'mutasi_barang.status',
                'mutasi_barang.created_at',
                'mutasi_barang.updated_at',
                'mutasi_barang.created_by'
            )
            ->whereDate('tanggal_mutasi', '>=', '2020-09-18')
            ->where('mutasi_barang.is_approved', 1)
            ->orderBy('mutasi_barang.id', 'asc')
            ->get();

        $logStock = [];
        foreach ($detail_mutasi as $dmb) {
            if ($dmb->status == 'approved') {
                $logStock[] = [
                    'tanggal'       => $dmb->tanggal_mutasi,
                    'id_barang'     => $dmb->id_barang,
                    'id_gudang'     => $dmb->id_gudang,
                    'id_user'       => $dmb->created_by != NULL ? $dmb->created_by : 1,
                    'id_referensi'  => $dmb->id,
                    'referensi'     => 'mutasi keluar',
                    'no_referensi'  => $dmb->id_mutasi_barang,
                    'qty_pcs'       => ($dmb->qty * $dmb->isi) + $dmb->qty_pcs,
                    'status'        => 'approved',
                    'created_at'    => $dmb->created_at,
                    'updated_at'    => $dmb->updated_at
                ];
            }
            if ($dmb->status == 'on the way') {
                $logStock[] = [
                    'tanggal'       => $dmb->tanggal_mutasi,
                    'id_barang'     => $dmb->id_barang,
                    'id_gudang'     => $dmb->id_gudang,
                    'id_user'       => $dmb->created_by != NULL ? $dmb->created_by : 1,
                    'id_referensi'  => $dmb->id,
                    'referensi'     => 'mutasi keluar',
                    'no_referensi'  => $dmb->id_mutasi_barang,
                    'qty_pcs'       => ($dmb->qty * $dmb->isi) + $dmb->qty_pcs,
                    'status'        => 'approved',
                    'created_at'    => $dmb->created_at,
                    'updated_at'    => $dmb->updated_at
                ];
                $logStock[] = [
                    'tanggal'       => $dmb->tanggal_mutasi,
                    'id_barang'     => $dmb->id_barang,
                    'id_gudang'     => $dmb->id_gudang,
                    'id_user'       => $dmb->created_by != NULL ? $dmb->created_by : 1,
                    'id_referensi'  => $dmb->id,
                    'referensi'     => 'mutasi keluar',
                    'no_referensi'  => $dmb->id_mutasi_barang,
                    'qty_pcs'       => ($dmb->qty * $dmb->isi) + $dmb->qty_pcs,
                    'status'        => 'on the way',
                    'created_at'    => $dmb->created_at,
                    'updated_at'    => $dmb->updated_at
                ];
            }
            if ($dmb->status == 'received') {
                $logStock[] = [
                    'tanggal'       => $dmb->tanggal_mutasi,
                    'id_barang'     => $dmb->id_barang,
                    'id_gudang'     => $dmb->id_gudang,
                    'id_user'       => $dmb->created_by != NULL ? $dmb->created_by : 1,
                    'id_referensi'  => $dmb->id,
                    'referensi'     => 'mutasi keluar',
                    'no_referensi'  => $dmb->id_mutasi_barang,
                    'qty_pcs'       => ($dmb->qty * $dmb->isi) + $dmb->qty_pcs,
                    'status'        => 'approved',
                    'created_at'    => $dmb->created_at,
                    'updated_at'    => $dmb->updated_at
                ];
                $logStock[] = [
                    'tanggal'       => $dmb->tanggal_mutasi,
                    'id_barang'     => $dmb->id_barang,
                    'id_gudang'     => $dmb->id_gudang,
                    'id_user'       => $dmb->created_by != NULL ? $dmb->created_by : 1,
                    'id_referensi'  => $dmb->id,
                    'referensi'     => 'mutasi keluar',
                    'no_referensi'  => $dmb->id_mutasi_barang,
                    'qty_pcs'       => ($dmb->qty * $dmb->isi) + $dmb->qty_pcs,
                    'status'        => 'on the way',
                    'created_at'    => $dmb->created_at,
                    'updated_at'    => $dmb->updated_at
                ];
                $logStock[] = [
                    'tanggal'       => $dmb->tanggal_realisasi,
                    'id_barang'     => $dmb->id_barang,
                    'id_gudang'     => $dmb->id_gudang,
                    'id_user'       => $dmb->created_by != NULL ? $dmb->created_by : 1,
                    'id_referensi'  => $dmb->id,
                    'referensi'     => 'mutasi keluar',
                    'no_referensi'  => $dmb->id_mutasi_barang,
                    'qty_pcs'       => ($dmb->qty * $dmb->isi) + $dmb->qty_pcs,
                    'status'        => 'received',
                    'created_at'    => $dmb->created_at,
                    'updated_at'    => $dmb->updated_at
                ];

                $stock2 = Stock::where('id_gudang', $dmb->ke)->where('id_barang', $dmb->id_barang)->first();
                $logStock[] = [
                    'tanggal'       => $dmb->tanggal_realisasi,
                    'id_barang'     => $stock2->id_barang,
                    'id_gudang'     => $stock2->id_gudang,
                    'id_user'       => $dmb->created_by != NULL ? $dmb->created_by : 1,
                    'id_referensi'  => $dmb->id,
                    'referensi'     => 'mutasi masuk',
                    'no_referensi'  => $dmb->id_mutasi_barang,
                    'qty_pcs'       => ($dmb->qty * $dmb->isi) + $dmb->qty_pcs,
                    'status'        => 'received',
                    'created_at'    => $dmb->created_at,
                    'updated_at'    => $dmb->updated_at
                ];
            }
        }

        $chunk_data = array_chunk($logStock, 1000);
        if (isset($chunk_data) && !empty($chunk_data)) {
            foreach ($chunk_data as $chunk_data_val) {
                LogStock::insert($chunk_data_val);
            }
        }
    }
}

<?php

use Illuminate\Database\Seeder;
use App\Models\DetailPenjualan;
use App\Models\HargaBarang;

class HargaDbpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $detail_penjualan = DetailPenjualan::chunk(1000, function ($details) {
            foreach ($details as $detail) {
                $tanggal_penjualan = $detail->penjualan->tanggal ?? '';
                if ($tanggal_penjualan != '') {
                    $id_barang = $detail->stock->id_barang;
                    $dbp = HargaBarang::where('id_barang', $id_barang)
                        ->where('tipe_harga', 'dbp')
                        ->whereDate('created_at', '<=', $tanggal_penjualan)
                        ->take(1)->first();
                    if ($dbp) {
                        $detail->update(['harga_dbp' => $dbp->harga]);
                    }
                }
            }
        });
    }
}
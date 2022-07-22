<?php


use Illuminate\Database\Seeder;
use App\Models\DetailReturPenjualan;
use App\Models\HargaBarang;

class HargaDbpReturSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $detail_retur = DetailReturPenjualan::chunk(1000, function ($details) {
            foreach ($details as $detail) {
                $tanggal_retur = $detail->retur_penjualan->sales_retur_date ?? '';
                if ($tanggal_retur != '') {
                    $id_barang = $detail->id_barang;
                    $dbp = HargaBarang::where('id_barang', $id_barang)
                        ->where('tipe_harga', 'dbp')
                        ->whereDate('created_at', '<=', $tanggal_retur)
                        ->take(1)->first();
                    if ($dbp) {
                        $detail->update(['harga_dbp' => $dbp->harga/1.1]);
                    }
                }
            }
        });
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVDetailPejualan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement(
            "CREATE VIEW v_detail_penjualan AS SELECT a.id, b.id as id_detail, a.id_toko, b.id_harga, c.id_barang, c.harga, d.kode_barang, d.nama_barang, d.isi, b.qty, b.qty_pcs,
                (b.qty + (b.qty_pcs/d.isi)) as ctn, e.id as id_segmen, e.nama_segmen, f.id as id_brand, f.nama_brand, g.disc_persen, disc_rupiah FROM penjualan a
                INNER JOIN detail_penjualan b ON a.id = b.id_penjualan
                INNER JOIN harga_barang c ON b.id_harga = c.id
                INNER JOIN barang d ON c.id_barang = d.id
                INNER JOIN segmen e ON d.id_segmen = e.id
                INNER JOIN brand f ON e.id_brand = f.id
                INNER JOIN promo g ON b.id_promo = g.id
                WHERE a.deleted_at IS NULL AND (a.status = 'approved' OR a.status = 'delivered') AND (b.qty !=0 OR b.qty_pcs != 0)
            "
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP VIEW v_detail_penjualan");
    }
}

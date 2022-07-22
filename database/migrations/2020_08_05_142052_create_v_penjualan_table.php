<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVPenjualanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE VIEW v_penjualan AS SELECT 'penjualan' as tipe, a.tanggal, a.id as no, a.no_invoice as kode, a.status, 
            c.id_gudang, d.id as id_barang, d.kode_barang, d.nama_barang, d.isi, b.qty, b.qty_pcs, f.nama_tim as tim, '' as dari_gudang, '' as ke_gudang  
            FROM penjualan a
                INNER JOIN detail_penjualan b ON a.id = b.id_penjualan
                INNER JOIN stock c ON b.id_stock = c.id
                INNER JOIN barang d ON d.id = c.id_barang
                INNER JOIN salesman e ON a.id_salesman = e.user_id
                INNER JOIN tim f ON e.id_tim = f.id
            WHERE a.deleted_at IS NULL
            ORDER BY a.id DESC
        ");

        DB::statement("
            CREATE VIEW v_adjusment_barang AS SELECT 'adjustment' as tipe, a.tanggal,a.id as no, a.no_adjustment as kode, a.status, a.id_gudang, d.id as id_barang, d.kode_barang, d.nama_barang, d.isi, b.qty_adj as qty, b.pcs_adj as qty_pcs, '' as tim, '' as dari_gudang, '' as ke_gudang 
			FROM adjustment a
                INNER JOIN detail_adjustment b ON a.id = b.id_adjustment
                INNER JOIN stock c ON b.id_stock = c.id
                INNER JOIN barang d ON d.id = c.id_barang
            WHERE a.deleted_at IS NULL
            ORDER BY a.id DESC
        ");

        DB::statement("
           CREATE VIEW v_mutasi_keluar AS SELECT 'mutasi keluar' as tipe, a.tanggal_mutasi as tanggal, a.id as no, a.id as kode, a.is_approved as status, a.dari as id_gudang, d.id as id_barang, d.kode_barang, d.nama_barang, d.isi, b.qty, b.qty_pcs, '' as tim, e.`nama_gudang` as dari_gudang, f.`nama_gudang` as ke_gudang 
			FROM mutasi_barang a
                INNER JOIN detail_mutasi_barang b ON a.id = b.id_mutasi_barang
                INNER JOIN stock c ON b.id_stock = c.id
                INNER JOIN barang d ON d.id = c.id_barang
                INNER JOIN gudang e ON a.dari = e.id
                INNER JOIN gudang f ON a.ke = f.id
           WHERE a.deleted_at IS NULL
           ORDER BY a.id DESC
        ");

        DB::statement("
           CREATE VIEW v_mutasi_masuk AS SELECT 'mutasi masuk' as tipe, a.tanggal_mutasi as tanggal, a.id as no, a.id as kode, a.is_approved as status, a.ke as id_gudang, d.id as id_barang, d.kode_barang, d.nama_barang, d.isi, b.qty, b.qty_pcs, '' as tim, e.`nama_gudang` as dari_gudang, f.`nama_gudang` as ke_gudang
			FROM mutasi_barang a
                INNER JOIN detail_mutasi_barang b ON a.id = b.id_mutasi_barang
                INNER JOIN stock c ON b.id_stock = c.id
                INNER JOIN barang d ON d.id = c.id_barang
                INNER JOIN gudang e ON a.dari = e.id
                INNER JOIN gudang f ON a.ke = f.id
            WHERE a.deleted_at IS NULL
            ORDER BY a.id DESC
        ");

        DB::statement("
            CREATE VIEW v_penerimaan_barang AS SELECT 'penerimaan barang' as tipe, a.tgl_datang as tanggal, a.id as no, a.no_pb as kode, a.is_approved as status, a.id_gudang, b.id_barang, c.kode_barang, c.nama_barang, c.isi, b.qty, b.qty_pcs, '' as tim, '' as dari_gudang, d.nama_gudang as ke_gudang FROM penerimaan_barang a
                INNER JOIN detail_penerimaan_barang b ON a.id = b.id_penerimaan_barang
                INNER JOIN barang c ON b.id_barang = c.id
                INNER JOIN gudang d ON a.`id_gudang` = d.id
            WHERE a.deleted_at IS NULL
            ORDER BY a.id DESC
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP VIEW v_penjualan");
        DB::statement("DROP VIEW v_adjusment_barang");
        DB::statement("DROP VIEW v_mutasi_keluar");
        DB::statement("DROP VIEW v_mutasi_masuk");
        DB::statement("DROP VIEW v_penerimaan_barang");
    }
}

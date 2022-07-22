<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateViewKunjunganSales extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("CREATE VIEW v_kunjungan_sales AS SELECT DATE(created_at) as tanggal, COUNT(*) as jumlah_kunjungan, id_user FROM kunjungan_sales GROUP BY DATE(created_at), id_user");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP VIEW v_kunjungan_sales");
    }
}

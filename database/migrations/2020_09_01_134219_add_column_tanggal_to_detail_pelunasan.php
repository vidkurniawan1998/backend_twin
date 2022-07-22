<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddColumnTanggalToDetailPelunasan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('detail_pelunasan_penjualan', function (Blueprint $table) {
            $table->date('tanggal')->nullable()->default(null)->after('id_penjualan');
        });

        DB::statement("UPDATE detail_pelunasan_penjualan SET tanggal = DATE(created_at)");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('detail_pelunasan_penjualan', function (Blueprint $table) {
            //
        });
    }
}

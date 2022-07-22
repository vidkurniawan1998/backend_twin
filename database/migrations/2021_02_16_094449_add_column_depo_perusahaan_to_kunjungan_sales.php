<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnDepoPerusahaanToKunjunganSales extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kunjungan_sales', function (Blueprint $table) {
            $table->unsignedInteger('id_depo')->after('status');
            $table->unsignedBigInteger('id_perusahaan')->after('id_depo');
            $table->index('id_depo');
            $table->index('id_perusahaan');
            $table->foreign('id_depo')->on('depo')->references('id')->onDelete('cascade');
            $table->foreign('id_perusahaan')->on('perusahaan')->references('id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kunjungan_sales', function (Blueprint $table) {
            $table->dropColumn('id_depo');
            $table->dropColumn('id_perusahaan');
        });
    }
}

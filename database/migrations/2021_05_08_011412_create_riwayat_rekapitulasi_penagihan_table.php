<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRiwayatRekapitulasiPenagihanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('riwayat_rekapitulasi_penagihan', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('tanggal_penagihan');
            $table->unsignedInteger('id_salesman');
            $table->unsignedInteger('id_penjualan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('riwayat_rekapitulasi_penagihan');
    }
}

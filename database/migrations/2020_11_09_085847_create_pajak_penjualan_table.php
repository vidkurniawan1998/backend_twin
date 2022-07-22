<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePajakPenjualanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('penjualan_pajak', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_penjualan');
            $table->string('npwp', 100);
            $table->string('nama_pkp', 255);
            $table->string('alamat_pkp', 255);
            $table->index('id_penjualan');
            $table->foreign('id_penjualan')->references('id')->on('penjualan')->onDelete('cascade');
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
        Schema::dropIfExists('penjualan_pajak');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFakturPembelianPenerimaanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('faktur_pembelian_penerimaan', function (Blueprint $table) {
            $table->unsignedBigInteger('id_faktur_pembelian');
            $table->integer('id_penerimaan_barang')->unsigned();
            $table->index('id_faktur_pembelian');
            $table->index('id_penerimaan_barang');

            $table->foreign('id_faktur_pembelian')->references('id')->on('faktur_pembelian')->onDelete('cascade');
            $table->foreign('id_penerimaan_barang')->references('id')->on('penerimaan_barang')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('faktur_pembelian_penerimaan');
    }
}

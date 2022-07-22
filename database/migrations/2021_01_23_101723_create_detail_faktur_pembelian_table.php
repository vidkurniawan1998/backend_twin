<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetailFakturPembelianTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detail_faktur_pembelian', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_faktur_pembelian');
            $table->integer('id_barang')->unsigned();
            $table->integer('pcs')->default(0);
            $table->decimal('harga', 15, 2)->default(0);
            $table->decimal('disc_persen', 6, 3)->default(0);
            $table->decimal('disc_value', 15, 2)->default(0);
            $table->index('id_faktur_pembelian');
            $table->index('id_barang');
            $table->timestamps();
        });

        Schema::table('detail_faktur_pembelian', function ($table) {
            $table->foreign('id_faktur_pembelian')->references('id')->on('faktur_pembelian');
            $table->foreign('id_barang')->references('id')->on('barang');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('detail_faktur_pembelian');
    }
}

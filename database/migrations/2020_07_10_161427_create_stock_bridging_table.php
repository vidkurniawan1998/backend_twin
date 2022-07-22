<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStockBridgingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_bridging', function (Blueprint $table) {
            $table->increments('id');
            $table->string('depo', 100);
            $table->string('gudang', 100);
            $table->string('kode', 20);
            $table->string('supp_code', 100);
            $table->string('barcode', 100);
            $table->string('nama_barang', 100);
            $table->string('deskripsi', 100);
            $table->double('harga');
            $table->integer('dus');
            $table->integer('pcs');
            $table->integer('volume');
            $table->integer('total_pcs');
            $table->double('nominal_per_pcs');
            $table->double('nominal');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('stock_bridging');
    }
}

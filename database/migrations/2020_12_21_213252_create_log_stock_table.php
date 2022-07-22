<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogStockTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_stock', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('tanggal');
            $table->unsignedInteger('id_barang');
            $table->unsignedInteger('id_gudang');
            $table->unsignedInteger('id_user');
            $table->bigInteger('id_referensi');
            $table->string('referensi', 50);
            $table->string('no_referensi', 50);
            $table->integer('qty_pcs');
            $table->string('status', 30);
            $table->index('id_barang');
            $table->index('id_gudang');
            $table->index('id_user');
            $table->foreign('id_barang')->on('barang')->references('id')->onDelete('cascade');
            $table->foreign('id_gudang')->on('gudang')->references('id')->onDelete('cascade');
            $table->foreign('id_user')->on('users')->references('id')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('log_stock');
    }
}

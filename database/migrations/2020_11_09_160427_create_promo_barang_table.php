<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoBarangTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_barang', function (Blueprint $table) {
            $table->unsignedInteger('promo_id');
            $table->unsignedInteger('barang_id');
            $table->index('promo_id');
            $table->index('barang_id');
            $table->foreign('promo_id')->references('id')->on('promo')->onDelete('cascade');
            $table->foreign('barang_id')->references('id')->on('barang')->onDelete('cascade');
            $table->primary(['promo_id','barang_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_barang');
    }
}

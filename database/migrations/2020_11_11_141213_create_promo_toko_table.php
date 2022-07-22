<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoTokoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_toko', function (Blueprint $table) {
            $table->unsignedInteger('promo_id');
            $table->unsignedInteger('toko_id');
            $table->index('promo_id');
            $table->index('toko_id');
            $table->foreign('promo_id')->references('id')->on('promo')->onDelete('cascade');
            $table->foreign('toko_id')->references('id')->on('toko')->onDelete('cascade');
            $table->primary(['promo_id','toko_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_toko');
    }
}

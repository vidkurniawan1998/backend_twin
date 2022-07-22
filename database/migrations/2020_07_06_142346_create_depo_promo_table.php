<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDepoPromoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_depo', function (Blueprint $table) {
            $table->unsignedInteger('promo_id');
            $table->unsignedInteger('depo_id');

            //FOREIGN KEY CONSTRAINTS
            $table->foreign('promo_id')->references('id')->on('promo')->onDelete('cascade');
            $table->foreign('depo_id')->references('id')->on('depo')->onDelete('cascade');

            //SETTING THE PRIMARY KEYS
            $table->primary(['promo_id','depo_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_depo');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSharingPromoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sharing_promo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('id_promo');
            $table->float('persen_principal')->default(0);
            $table->float('persen_dist')->default(0);
            $table->float('nominal_principal')->default(0);
            $table->float('nominal_dist')->default(0);
            $table->float('extra_principal')->default(0);
            $table->float('extra_dist')->default(0);
            $table->index('id_promo');
            $table->foreign('id_promo')->references('id')->on('promo')->onDelete('cascade');
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
        Schema::dropIfExists('sharing_promo');
    }
}

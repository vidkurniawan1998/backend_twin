<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrupTokoLogistik extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grup_toko_logistik', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nama_grup', 255);
            $table->unsignedInteger('created_by');
            $table->index('created_by');
            $table->foreign('created_by')->on('users')->references('id')->onDelete('cascade');
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
        Schema::dropIfExists('grup_toko_logistik');
    }
}

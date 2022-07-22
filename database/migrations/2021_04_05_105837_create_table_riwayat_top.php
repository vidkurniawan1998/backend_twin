<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableRiwayatTop extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('riwayat_top', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('id_toko');
            $table->integer('top')->default(0);
            $table->unsignedInteger('update_by');
            $table->index('id_toko');
            $table->index('update_by');
            $table->foreign('id_toko')->references('id')->on('toko');
            $table->foreign('update_by')->references('id')->on('users');
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
        Schema::dropIfExists('riwayat_top');
    }
}

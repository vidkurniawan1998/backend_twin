<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBarangDepoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('barang_depo', function (Blueprint $table) {
            $table->unsignedInteger('barang_id');
            $table->unsignedInteger('depo_id');
            
            //FOREIGN KEY CONSTRAINTS
            $table->foreign('barang_id')->references('id')->on('barang')->onDelete('cascade');
            $table->foreign('depo_id')->references('id')->on('depo')->onDelete('cascade');

            //SETTING THE PRIMARY KEYS
            $table->primary(['barang_id','depo_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('barang_depo');
    }
}

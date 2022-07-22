<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMutasiBarangTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mutasi_barang', function (Blueprint $table) {
            $table->increments('id', 1000000);
            $table->date('tanggal_mutasi');
            $table->unsignedInteger('dari');
            $table->unsignedInteger('ke');
            $table->text('keterangan')->nullable();
            $table->boolean('is_approved')->default(0);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('detail_mutasi_barang', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_mutasi_barang')->unsigned();
            $table->integer('id_stock')->unsigned();
            $table->integer('qty')->default(0)->unsigned();
            $table->integer('qty_pcs')->default(0)->unsigned();
            $table->text('keterangan')->nullable();
            $table->integer('created_by')->unsigned()->nullable();
            $table->integer('updated_by')->unsigned()->nullable();
            $table->integer('deleted_by')->unsigned()->nullable();
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
        Schema::dropIfExists('mutasi_barang');
        Schema::dropIfExists('detail_mutasi_barang');
    }
}

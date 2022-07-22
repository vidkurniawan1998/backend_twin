<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBarangTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('barang', function (Blueprint $table) {
            $table->increments('id');
            $table->string('kode_barang', 15);//->primary();
            $table->string('barcode', 20)->nullable();
            $table->string('nama_barang');
            $table->float('berat')->unsigned()->default(0);
            $table->smallInteger('isi')->unsigned()->default(0);
            $table->string('satuan', 20)->nullable()->default('CAR');
            $table->string('id_segmen', 10)->nullable();
            $table->text('deskripsi')->nullable();
            $table->text('gambar')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('harga_barang', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_barang');
            $table->enum('tipe_harga', ['dbp', 'rbp', 'hcobp', 'wbp', 'cbp', 'lka', 'nka', 'extra']);
            $table->unsignedInteger('harga');
            $table->unsignedInteger('created_by')->nullable();
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
        Schema::dropIfExists('barang');
        Schema::dropIfExists('harga_barang');
    }
}

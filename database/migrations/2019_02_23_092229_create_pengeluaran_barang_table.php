<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePengeluaranBarangTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::create('pengeluaran_barang', function (Blueprint $table) {
        //     $table->increments('id', 1000000);
        //     $table->date('tanggal_keluar');
        //     $table->unsignedInteger('dari');
        //     $table->unsignedInteger('ke')->nullable();
        //     $table->text('keterangan')->nullable();
        //     $table->boolean('is_approved')->default(0);
        //     $table->unsignedInteger('created_by')->nullable();
        //     $table->unsignedInteger('updated_by')->nullable();
        //     $table->unsignedInteger('deleted_by')->nullable();
        //     $table->timestamps();
        //     $table->softDeletes();
        // });

        Schema::create('detail_pengeluaran_barang', function (Blueprint $table) {
            $table->increments('id');
            // $table->unsignedInteger('id_pengeluaran_barang');
            $table->unsignedInteger('pengiriman');
            $table->string('id_pengiriman',10)->nullable();
            $table->unsignedInteger('id_stock');
            $table->unsignedInteger('qty')->default(0);
            $table->unsignedInteger('qty_pcs')->default(0);
            $table->text('keterangan')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            // $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            // $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::dropIfExists('pengeluaran_barang');
        Schema::dropIfExists('detail_pengeluaran_barang');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHargaBarangAktifTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('harga_barang_aktif', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('id_harga_barang');
            $table->unsignedInteger('id_barang');
            $table->float('harga_non_ppn', 12, 2, true);
            $table->string('tipe_harga',200);
            $table->float('harga', 12, 2, true);
            $table->float('ppn', 8, 2, true)->default(10);
            $table->float('ppn_value', 12, 2, true)->default(0);
            $table->unsignedBigInteger('created_by');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('id_harga_barang')->on('harga_barang')->references('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('harga_barang_aktif');
    }
}

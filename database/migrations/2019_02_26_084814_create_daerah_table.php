<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDaerahTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('provinsi', function (Blueprint $table) {
            $table->string('id',2);
            $table->string('nama_provinsi');
        });

        Schema::create('kabupaten', function (Blueprint $table) {
            $table->string('id',4);
            $table->string('id_provinsi',2);
            $table->string('nama_kabupaten');
        });

        Schema::create('kecamatan', function (Blueprint $table) {
            $table->string('id',7);
            $table->string('id_kabupaten',4);
            $table->string('nama_kecamatan');
        });

        Schema::create('kelurahan', function (Blueprint $table) {
            $table->string('id',10);
            $table->string('id_kecamatan',7);
            $table->string('nama_kelurahan');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('provinsi');
        Schema::dropIfExists('kabupaten');
        Schema::dropIfExists('kecamatan');
        Schema::dropIfExists('kelurahan');
    }
}

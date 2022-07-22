<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTokoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('toko', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama_toko');
            $table->enum('tipe', ['R1', 'R2', 'W', 'MM', 'KOP', 'HRC', 'HCO', 'PNB'])->nullable();
            $table->string('pemilik')->nullable();
            $table->string('no_acc', 20)->nullable();
            $table->string('cust_no', 20)->nullable();
            $table->string('kode_mars', 20)->nullable();
            $table->string('telepon', 20)->nullable();
            $table->text('alamat')->nullable();
            // $table->text('gps')->nullable();
            $table->text('latitude')->nullable();
            $table->text('longitude')->nullable();
            $table->string('kode_pos', 5)->nullable();
            // $table->string('id_provinsi',2)->nullable();
            // $table->string('id_kabupaten',4)->nullable();
            // $table->string('id_kecamatan',7)->nullable();
            $table->string('id_kelurahan',10)->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
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
        Schema::dropIfExists('toko');
    }
}

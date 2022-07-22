<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNpwpExternalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('npwp_external', function (Blueprint $table) {
            $table->id();
            $table->string('kode_outlet', 50)->unique();
            $table->string('nama_toko', 150)->nullable();
            $table->string('npwp', 100)->nullable();
            $table->string('nama_pkp', 150)->nullable();
            $table->string('alamat_pkp', 255)->nullable();
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
        Schema::dropIfExists('npwp_external');
    }
}

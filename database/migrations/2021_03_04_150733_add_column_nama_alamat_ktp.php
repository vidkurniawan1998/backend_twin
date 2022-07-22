<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnNamaAlamatKtp extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ketentuan_toko', function (Blueprint $table) {
            $table->string('nama_ktp', 200)->default('');
            $table->string('alamat_ktp', 255)->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ketentuan_toko', function (Blueprint $table) {
            $table->dropColumn(['nama_ktp', 'alamat_ktp']);
        });
    }
}

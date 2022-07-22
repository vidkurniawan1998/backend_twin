<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnNamaPkpToKetentuanToko extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ketentuan_toko', function (Blueprint $table) {
            $table->string('nama_pkp', 255)->default(null)->nullable();
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
            $table->dropColumn('nama_pkp');
        });
    }
}

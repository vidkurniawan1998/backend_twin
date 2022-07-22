<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnAlamatPkpToKetentuanToko extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ketentuan_toko', function (Blueprint $table) {
            $table->text('alamat_pkp')->default(null)->nullable()->after('nama_pkp');
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
            $table->dropColumn('alamat_pkp');
        });
    }
}

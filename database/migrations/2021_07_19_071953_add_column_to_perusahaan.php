<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToPerusahaan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('perusahaan', function (Blueprint $table) {
            $table->string('npwp')->nullable()->after('nama_perusahaan');
            $table->string('nama_pkp')->nullable()->after('npwp');
            $table->string('alamat_pkp')->nullable()->after('nama_pkp');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('perusahaan', function (Blueprint $table) {
            $table->dropColumn('npwp');
            $table->dropColumn('nama_pkp');
            $table->dropColumn('alamat_pkp');
        });
    }
}

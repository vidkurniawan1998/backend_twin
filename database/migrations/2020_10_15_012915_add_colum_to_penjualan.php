<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumToPenjualan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('penjualan', function (Blueprint $table) {
            $table->date('tanggal_jadwal')->default(null)->nullable()->after('approved_by');
            $table->unsignedInteger('driver_id')->nullable()->after('tanggal_jadwal');
            $table->unsignedInteger('checker_id')->nullable()->after('driver_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('penjualan', function (Blueprint $table) {
            $table->dropColumn('tanggal_jadwal');
            $table->dropColumn('driver_id');
            $table->dropColumn('checker_id');
        });
    }
}

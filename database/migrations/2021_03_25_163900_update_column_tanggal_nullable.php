<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateColumnTanggalNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('faktur_pembelian', function (Blueprint $table) {
            $table->date('tanggal')->nullable()->change();
            $table->date('tanggal_invoice')->nullable()->change();
            $table->date('tanggal_jatuh_tempo')->nullable()->change();
            $table->date('tanggal_bayar')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('faktur_pembelian', function (Blueprint $table) {
            //
        });
    }
}

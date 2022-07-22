<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToReturPenjualan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('retur_penjualan', function (Blueprint $table) {
            $table->string('faktur_pajak_pembelian')->nullable()->after('faktur_pajak');
            $table->date('tanggal_faktur_pajak_pembelian')->nullable()->after('faktur_pajak_pembelian');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('retur_penjualan', function (Blueprint $table) {
            $table->dropColumn('faktur_pajak_pembelian');
            $table->dropColumn('tanggal_faktur_pajak_pembelian');
        });
    }
}

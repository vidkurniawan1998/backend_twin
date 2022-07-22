<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnMutasiPendingToStockAwal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stock_awal', function (Blueprint $table) {
            $table->integer('qty_mutasi_pending');
            $table->integer('qty_pcs_mutasi_pending');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stock_awal', function (Blueprint $table) {
            $table->dropColumn('qty_mutasi_pending');
            $table->dropColumn('qty_pcs_mutasi_pending');
        });
    }
}

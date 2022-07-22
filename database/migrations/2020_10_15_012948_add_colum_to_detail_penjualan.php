<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumToDetailPenjualan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('detail_penjualan', function (Blueprint $table) {
            $table->integer('qty_loading')->default(0)->nullable()->after('order_pcs');
            $table->integer('qty_pcs_loading')->default(0)->nullable()->after('qty_loading');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('detail_penjualan', function (Blueprint $table) {
            $table->dropColumn('qty_loading');
            $table->dropColumn('qty_pcs_loading');
        });
    }
}

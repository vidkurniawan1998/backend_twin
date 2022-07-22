<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnHargaDbpToDetailReturPenjualan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('detail_retur_penjualan', function (Blueprint $table) {
            $table->float('harga_dbp', 12, 2)->default(0)->after('harga');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('detail_retur_penjualan', function (Blueprint $table) {
            $table->dropColumn('harga_dbp');
        });
    }
}

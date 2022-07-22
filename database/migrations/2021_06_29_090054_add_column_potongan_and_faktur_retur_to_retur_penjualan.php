<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnPotonganAndFakturReturToReturPenjualan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('retur_penjualan', function (Blueprint $table) {
            $table->double('potongan', 5, 2)->after('id_mitra')->default(0);
            $table->string('faktur_pajak')->default('')->after('potongan');
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
            $table->dropColumn(['potongan', 'faktur_pajak']);
        });
    }
}

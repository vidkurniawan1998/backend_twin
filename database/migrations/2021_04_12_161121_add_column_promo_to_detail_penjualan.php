<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnPromoToDetailPenjualan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('detail_penjualan', function (Blueprint $table) {
            $table->float('disc_persen', 4, 2)->unsigned()->default('0')->after('harga_jual');
            $table->decimal('disc_rupiah', 8,2)->default('0')->after('disc_persen');
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
            $table->dropColumn(['disc_persen', 'disc_rupiah']);
        });
    }
}

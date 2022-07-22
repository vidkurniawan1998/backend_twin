<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnDiscToDetailReturPenjualan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('detail_retur_penjualan', function (Blueprint $table) {
            $table->decimal('disc_persen', 5,2)->default(0)->after('harga');
            $table->decimal('disc_nominal', 10,2)->default(0)->after('disc_persen');
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
            $table->dropColumn(['disc_persen', 'disc_nominal']);
        });
    }
}

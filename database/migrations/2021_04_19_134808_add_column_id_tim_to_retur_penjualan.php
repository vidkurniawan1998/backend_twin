<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnIdTimToReturPenjualan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('retur_penjualan', function (Blueprint $table) {
            $table->unsignedInteger('id_tim')->after('id_salesman')->default(0);
            $table->index('id_tim');
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
            $table->dropColumn('id_tim');
        });
    }
}

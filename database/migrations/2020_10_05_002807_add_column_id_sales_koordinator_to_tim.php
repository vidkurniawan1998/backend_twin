<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnIdSalesKoordinatorToTim extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tim', function (Blueprint $table) {
            $table->integer('id_sales_koordinator')->nullable()->after('id_sales_supervisor');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tim', function (Blueprint $table) {
            $table->dropColumn('id_sales_koordinator');
        });
    }
}

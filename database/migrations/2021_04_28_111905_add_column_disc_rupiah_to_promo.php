<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnDiscRupiahToPromo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo', function (Blueprint $table) {
            $table->integer('disc_rupiah_distributor')->default(0)->after('disc_rupiah');
            $table->integer('disc_rupiah_principal')->default(0)->after('disc_rupiah_distributor');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promo', function (Blueprint $table) {
            $table->dropColumn('disc_rupiah_distributor');
            $table->dropColumn('disc_rupiah_principal');
        });
    }
}

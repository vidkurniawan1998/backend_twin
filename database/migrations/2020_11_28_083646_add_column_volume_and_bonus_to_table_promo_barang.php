<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnVolumeAndBonusToTablePromoBarang extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_barang', function (Blueprint $table) {
            $table->integer('volume')->after('barang_id')->default(0);
            $table->integer('bonus_pcs')->after('volume')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promo_barang', function (Blueprint $table) {
            $table->dropColumn('volume');
            $table->dropColumn('bonus_pcs');
        });
    }
}

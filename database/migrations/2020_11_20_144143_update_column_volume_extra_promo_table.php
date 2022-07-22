<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateColumnVolumeExtraPromoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo', function (Blueprint $table) {
            $table->dropColumn('volume_extra');
        });

        Schema::table('promo', function (Blueprint $table) {
            $table->integer('volume_extra')->after('id_barang')->default(0);
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
            $table->dropColumn('volume_extra');
        });

        Schema::table('promo', function (Blueprint $table) {
            $table->tinyInteger('volume_extra')->after('id_barang')->default(0);
        });
    }
}

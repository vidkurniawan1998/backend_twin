<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsDiscountTablePromo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo', function (Blueprint $table) {
            $table->float('disc_1', 4, 2)->unsigned()->default('0.00')->nullable()->after('disc_persen');
            $table->float('disc_2', 4, 2)->unsigned()->default('0.00')->nullable()->after('disc_1');
            $table->float('disc_3', 4, 2)->unsigned()->default('0.00')->nullable()->after('disc_2');
            $table->float('disc_4', 4, 2)->unsigned()->default('0.00')->nullable()->after('disc_3');
            $table->float('disc_5', 4, 2)->unsigned()->default('0.00')->nullable()->after('disc_4');
            $table->float('disc_6', 4, 2)->unsigned()->default('0.00')->nullable()->after('disc_5');
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
            $table->dropColumn('disc_1');
            $table->dropColumn('disc_2');
            $table->dropColumn('disc_3');
            $table->dropColumn('disc_4');
            $table->dropColumn('disc_5');
            $table->dropColumn('disc_6');
        });
    }
}

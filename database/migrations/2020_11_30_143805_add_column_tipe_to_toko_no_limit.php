<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnTipeToTokoNoLimit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('toko_no_limit', function (Blueprint $table) {
            $table->string('tipe', 50)->default('od');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('toko_no_limit', function (Blueprint $table) {
            $table->dropColumn('tipe');
        });
    }
}

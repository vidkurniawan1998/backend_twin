<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnIdPerusahaanToDepo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('depo', function (Blueprint $table) {
            $table->unsignedInteger('id_perusahaan')->after('id')->default(1);
            $table->index('id_perusahaan');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('depo', function (Blueprint $table) {
            $table->dropColumn('id_perusahaan');
        });
    }
}

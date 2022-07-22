<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnIdPerusahaanToPrincipal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('principal', function (Blueprint $table) {
            $table->unsignedInteger('id_perusahaan')->after('telp')->default(1);
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
        Schema::table('principal', function (Blueprint $table) {
            $table->dropColumn('id_perusahaan');
        });
    }
}

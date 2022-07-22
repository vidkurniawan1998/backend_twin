<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToDepo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('depo', function (Blueprint $table) {
            $table->string('telp')->after('alamat')->default(null)->nullable();
            $table->string('fax')->after('telp')->default(null)->nullable();
            $table->string('kabupaten')->after('fax')->default(null)->nullable();
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
            $table->dropColumn('telp');
            $table->dropColumn('fax');
            $table->dropColumn('kabupaten');
        });
    }
}

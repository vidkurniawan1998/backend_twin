<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnRemarkCloseToPenjualan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('penjualan', function (Blueprint $table) {
            $table->string('remark_close', 50)->after('closed_by')->nullable()->default(null);
            DB::statement("ALTER TABLE penjualan CHANGE COLUMN status status ENUM('waiting','canceled','approved','loaded','delivered','closed') NOT NULL DEFAULT 'waiting'");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('penjualan', function (Blueprint $table) {
            $table->dropColumn('remark_close');
        });
    }
}

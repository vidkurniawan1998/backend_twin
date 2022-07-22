<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnFakturPajakToFakturPembelian extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('faktur_pembelian', function (Blueprint $table) {
            $table->string('faktur_pajak')->nullable()->after('no_invoice');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('faktur_pembelian', function (Blueprint $table) {
            $table->dropColumn('faktur_pajak');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnQtyDetailFakturPembelian extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('detail_faktur_pembelian', function (Blueprint $table) {
            $table->integer('qty')->after('id_barang')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('detail_faktur_pembelian', function (Blueprint $table) {
            $table->dropColumn('qty');
        });
    }
}

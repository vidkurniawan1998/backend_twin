<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPpnToHargaBarangTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('harga_barang', function (Blueprint $table) {
            $table->float('ppn', 8, 2, true)->after('harga')->default(10);
            $table->float('ppn_value', 12, 2, true)->after('ppn')->default(0);
            $table->float('harga_non_ppn', 12, 2, true)->after('id_barang');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('harga_barang', function (Blueprint $table) {
            $table->dropColumn('ppn');
            $table->dropColumn('ppn_value');
            $table->dropColumn('harga_non_ppn');
        });
    }
}

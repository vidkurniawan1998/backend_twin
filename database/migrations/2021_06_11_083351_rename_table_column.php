<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameTableColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('invoice_header', 'mitra');
        Schema::table('mitra', function(Blueprint $table) {
            $table->renameColumn('kode_depo', 'kode_mitra');
        });

        Schema::table('toko', function(Blueprint $table) {
            $table->renameColumn('id_invoice_header', 'id_mitra');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('mitra', 'invoice_header');

        Schema::table('invoice_header', function(Blueprint $table) {
            $table->renameColumn('kode_mitra', 'kode_depo');
        });

        Schema::table('toko', function(Blueprint $table) {
            $table->renameColumn('id_mitra', 'id_invoice_header');
        });
    }
}

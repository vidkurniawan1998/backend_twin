<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateStockOpnameTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_opname', function (Blueprint $table) {
            $table->increments('id', 1000000);
            $table->date('tanggal_so');
            $table->unsignedInteger('id_gudang');
            $table->text('keterangan')->nullable();
            $table->boolean('is_approved')->default(0);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement("ALTER TABLE stock_opname AUTO_INCREMENT = 1000001;");

        Schema::create('detail_stock_opname', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_stock_opname')->unsigned();
            $table->integer('id_stock')->unsigned();
            $table->integer('qty')->default(0);
            $table->integer('qty_pcs')->default(0);
            $table->integer('qty_fisik')->default(0)->unsigned();
            $table->integer('qty_pcs_fisik')->default(0)->unsigned();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stock_opname');
        Schema::dropIfExists('detail_stock_opname');
    }
}

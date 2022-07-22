<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo', function (Blueprint $table) {
            $table->increments('id');
            // $table->string('kode_promo',10);
            $table->string('nama_promo');
            $table->enum('status', ['non_active', 'active'])->default('non_active');
            $table->text('keterangan')->nullable();
            $table->float('disc_persen', 4, 2)->unsigned()->default('0.00')->nullable();
            $table->unsignedBigInteger('disc_rupiah')->default('0')->nullable();
            // $table->unsignedInteger('id_barang')->default('0')->nullable();
            $table->string('id_barang', 10)->nullable();
            $table->unsignedBigInteger('pcs_extra')->default('0')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
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
        Schema::dropIfExists('promo');
    }
}

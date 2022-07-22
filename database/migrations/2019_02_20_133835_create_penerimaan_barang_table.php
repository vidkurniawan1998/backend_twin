<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePenerimaanBarangTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('penerimaan_barang', function (Blueprint $table) {
            $table->increments('id');
            $table->string('no_pb',20)->nullable();
            $table->string('no_do',20);
            $table->string('no_spb',20)->nullable();
            $table->string('id_pembelian',10)->nullable();
            $table->unsignedInteger('id_principal');
            $table->unsignedInteger('id_gudang');
            $table->date('tgl_kirim');
            $table->date('tgl_datang');
            $table->date('tgl_bongkar');
            $table->string('driver',100)->nullable();
            $table->string('transporter',100)->nullable();
            $table->string('no_pol_kendaraan',11)->nullable();
            $table->text('keterangan')->nullable();
            $table->boolean('is_approved')->default(0);
            $table->text('scan')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('detail_penerimaan_barang', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_penerimaan_barang');
            $table->unsignedInteger('id_barang');
            $table->unsignedInteger('id_harga')->nullable();
            $table->unsignedInteger('qty');
            $table->text('keterangan')->nullable();
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
        Schema::dropIfExists('penerimaan_barang');
        Schema::dropIfExists('detail_penerimaan_barang');
    }
}

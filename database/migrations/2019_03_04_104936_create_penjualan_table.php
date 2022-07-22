<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePenjualanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('penjualan', function (Blueprint $table) {
            $table->increments('id', 1000000); //nomor surat pesanan
            // $table->unsignedInteger('dari_gudang');
            $table->unsignedInteger('id_toko');
            $table->unsignedInteger('id_salesman');
            $table->date('tanggal');
            $table->enum('tipe_pembayaran', ['credit', 'cash', 'bg', 'trs'])->default('credit');
            // $table->enum('tipe_harga', ['rbp','hcobp','wbp','lka','nka'])->default('wbp');
            // $table->float('disc_persen', 3, 2)->nullable();
            // $table->integer('disc_rupiah')->nullable();
            $table->text('keterangan')->nullable();
            // $table->boolean('is_approved')->default(0);
            $table->enum('status', ['waiting', 'canceled', 'approved', 'loaded','delivered'])->default('waiting');
            $table->string('id_retur',10)->nullable();
            $table->string('id_pengiriman',10)->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('detail_penjualan', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_penjualan');
            $table->unsignedInteger('id_stock');
            $table->unsignedInteger('qty')->default(0);
            $table->unsignedInteger('qty_pcs')->default(0);
            // $table->unsignedInteger('qty_extra')->default(0);
            $table->unsignedInteger('id_harga');
            $table->string('id_promo',10)->nullable()->default('0');
            // $table->unsignedInteger('id_promo')->nullable();
            // $table->float('disc_persen', 3, 2)->unsigned()->nullable();
            // $table->integer('disc_rupiah')->unsigned()->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('penjualan');
        Schema::dropIfExists('detail_penjualan');
    }
}

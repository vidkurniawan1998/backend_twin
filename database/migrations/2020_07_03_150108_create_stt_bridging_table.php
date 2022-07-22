<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSttBridgingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stt_bridging', function (Blueprint $table) {
            $table->increments('id');
            $table->string('gudang', 100)->nullable();
            $table->string('outlet_code', 255)->nullable();
            $table->string('outlet_name', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('kabupaten', 150)->nullable();
            $table->string('kecamatan', 150)->nullable();
            $table->string('kode_pos', 25)->nullable();
            $table->string('selektif', 255)->nullable();
            $table->string('npwp', 255)->nullable();
            $table->string('nama_pkp', 255)->nullable();
            $table->string('alamat_pkp', 255)->nullable();
            $table->string('cust_type', 10)->nullable();
            $table->string('type_outlet', 10)->nullable();
            $table->string('distrik', 25)->nullable();
            $table->string('lokasi_pasar', 255)->nullable();
            $table->string('nama_pasar', 255)->nullable();
            $table->string('rute', 255)->nullable();
            $table->string('kunjungan', 255)->nullable();
            $table->string('item_code', 100)->nullable();
            $table->string('s_code', 100)->nullable();
            $table->string('status', 255)->nullable();
            $table->string('kode_supp', 25)->nullable();
            $table->string('grup', 50)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->string('brand', 100)->nullable();
            $table->string('segmen', 255)->nullable();
            $table->string('kemasan', 255)->nullable();
            $table->string('satuan', 255)->nullable();
            $table->string('salesman_name', 255)->nullable();
            $table->string('team', 100)->nullable();
            $table->string('spv', 100)->nullable();
            $table->integer('qty_dus')->length(4)->default(0)->unsigned();
            $table->integer('qty_pcs')->length(4)->default(0)->unsigned();
            $table->integer('in_pcs')->length(4)->default(0)->unsigned();
            $table->double('harga_1', 11,2)->default(0);
            $table->double('harga_2', 11,2)->default(0);
            $table->double('harga_trans')->default(0);
            $table->integer('volume')->length(4)->default(0)->unsigned();
            $table->integer('tahun')->length(4)->default(0)->unsigned();
            $table->integer('bulan')->length(2)->default(0)->unsigned();
            $table->integer('hari')->length(2)->default(0)->unsigned();
            $table->date('transdate');
            $table->double('subtotal', 11,2)->default(0);
            $table->double('diskon', 11,2)->default(0);
            $table->string('proposal')->nullable();
            $table->double('ppn', 11,2)->default(0);
            $table->double('total', 11,2)->default(0);
            $table->double('hpp', 11,2)->default(0);
            $table->double('harga_nett', 11,2)->default(0);
            $table->integer('week')->length(2)->default(0)->unsigned();
            $table->string('driver')->nullable();
            $table->string('helper')->nullable();
            $table->string('number')->nullable();
            $table->string('pick_slip')->nullable();
            $table->double('berat', 5,2)->default(0);
            $table->string('no_pajak', 100)->nullable();
            $table->string('cust_id', 10)->nullable();
            $table->string('outlet_id', 10)->nullable();
            $table->string('salesman', 10)->nullable();
            $table->string('category', 10)->nullable();
            $table->string('subcategory', 10)->nullable();
            $table->string('pref_vendor', 200)->nullable();
            $table->integer('pembayaran')->length(4)->default(0)->unsigned();
            $table->integer('term')->length(2)->nullable()->unsigned();
            $table->string('keterangan', 200)->nullable();
            $table->string('vencode', 20)->nullable();
            $table->string('pro_invoice', 100)->nullable();
            $table->string('pjp', 100)->nullable();
            $table->string('no_po', 100)->nullable();
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('stt_bridging');
    }
}

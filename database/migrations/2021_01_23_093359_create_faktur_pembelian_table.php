<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFakturPembelianTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('faktur_pembelian', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('no_invoice', 100);
            $table->date('tanggal');
            $table->date('tanggal_invoice');
            $table->date('tanggal_jatuh_tempo');
            $table->date('tanggal_bayar');
            $table->decimal('disc_persen', 6, 3);
            $table->decimal('disc_value', 15, 2);
            $table->enum('status', ['input', 'approved', 'paid']);
            $table->integer('id_principal')->unsigned();
            $table->integer('id_user')->unsigned();
            $table->integer('id_depo')->unsigned();
            $table->integer('ppn')->default(0);
            $table->unsignedBigInteger('id_perusahaan');
            $table->index('id_principal');
            $table->index('id_user');
            $table->index('id_depo');
            $table->index('id_perusahaan');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('faktur_pembelian', function ($table) {
            $table->foreign('id_user')->references('id')->on('users');
            $table->foreign('id_principal')->references('id')->on('principal');
            $table->foreign('id_depo')->references('id')->on('depo');
            $table->foreign('id_perusahaan')->references('id')->on('perusahaan');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('faktur_pembelian');
    }
}

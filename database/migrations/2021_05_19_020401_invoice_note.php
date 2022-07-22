<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InvoiceNote extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //Schema::dropIfExists('invoice_note');
        Schema::create('invoice_note', function (Blueprint $table) {
            $table->increments('id'); 
            $table->unsignedInteger('id_penjualan');
            $table->string('no_invoice', 16);
            $table->date('tanggal');
            $table->text('keterangan')->nullable();
            $table->text('keterangan_reschedule')->nullable();
            $table->enum('status', ['belum_dikunjungi', 'dikunjungi'])->default('belum_dikunjungi');
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('invoice_note', function ($table) {
            $table->foreign('id_penjualan')->references('id')->on('penjualan')->onDelete('cascade');
            $table->unique(['id_penjualan', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RiwayatInvoiceNote extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //Schema::dropIfExists('riwayat_invoice_note');
        schema::create('riwayat_invoice_note', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_invoice_note');
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

        Schema::table('riwayat_invoice_note', function ($table) {
            $table->foreign('id_invoice_note')->references('id')->on('invoice_note');
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

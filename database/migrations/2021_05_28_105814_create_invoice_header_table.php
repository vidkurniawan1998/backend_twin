<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoiceHeaderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_header', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('kode_depo', 3);
            $table->string('perusahaan', 150);
            $table->string('alamat', 255);
            $table->string('telp', 50);
            $table->string('fax', 100);
            $table->string('kabupaten', 100);
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
        Schema::dropIfExists('invoice_header');
    }
}

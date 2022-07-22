<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableRiwayatLimitKredit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('riwayat_limit_kredit', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_toko');
            $table->decimal('limit_credit', 20,2)->default(0);
            $table->unsignedInteger('update_by');
            $table->index('id_toko');
            $table->index('update_by');
            $table->foreign('id_toko')->references('id')->on('toko');
            $table->foreign('update_by')->references('id')->on('users');
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
        Schema::dropIfExists('riwayat_limit_kredit');
    }
}

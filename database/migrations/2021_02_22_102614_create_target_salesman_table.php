<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTargetSalesmanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('target_salesman', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_perusahaan');
            $table->unsignedInteger('id_depo');
            $table->unsignedInteger('id_user');
            $table->date('mulai_tanggal');
            $table->date('sampai_tanggal');
            $table->integer('hari_kerja')->default(0);
            $table->decimal('target', 15, 2)->default(0);
            $table->unsignedInteger('input_by');
            $table->index('id_perusahaan');
            $table->index('id_depo');
            $table->index('id_user');
            $table->index('input_by');
            $table->foreign('id_perusahaan')->references('id')->on('perusahaan')->onDelete('cascade');
            $table->foreign('id_depo')->references('id')->on('depo')->onDelete('cascade');
            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('input_by')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('target_salesman');
    }
}

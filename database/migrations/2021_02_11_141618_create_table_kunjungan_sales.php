<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableKunjunganSales extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kunjungan_sales', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('id_toko');
            $table->unsignedInteger('id_user');
            $table->decimal('latitude', 20,14)->default(0);
            $table->decimal('longitude', 20,14)->default(0);
            $table->enum('status', ['order', 'tidak order', 'tutup'])->default('tidak order');
            $table->string('keterangan', 255)->default('')->nullable();
            $table->index('id_toko');
            $table->index('id_user');
            $table->foreign('id_toko')->references('id')->on('toko')->onDelete('cascade');
            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('kunjungan_sales');
    }
}

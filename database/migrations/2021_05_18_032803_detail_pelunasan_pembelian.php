<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DetailPelunasanPembelian extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('detail_pelunasan_pembelian', function (Blueprint $table) {
            $table->engine = 'MyISAM';
            $table->increments('id');
            $table->bigInteger('id_faktur_pembelian');
            $table->date('tanggal');
            $table->enum('tipe', ['tunai','transfer','bilyet_giro','saldo_retur','lainnya']);
            $table->integer('nominal');
            $table->enum('status', ['waiting','approved','rejected']);
            $table->string('bank', 30)->default(null)->nullable();
            $table->string('no_rekening', 30)->default(null)->nullable();
            $table->string('no_bg', 30)->default(null)->nullable();
            $table->date('jatuh_tempo_bg')->default(null)->nullable();
            $table->text('keterangan')->default(null)->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('detail_pelunasan_pembelian', function ($table) {
            $table->foreign('id_faktur_pembelian')->references('id')->on('faktur_pembelian')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('detail_pelunasan_pembelian');
    }
}

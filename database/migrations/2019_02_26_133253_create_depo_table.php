<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDepoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('depo', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama_depo');
            $table->unsignedInteger('id_gudang');
            $table->unsignedInteger('id_gudang_bs');
            $table->unsignedInteger('id_gudang_tg');
            $table->unsignedInteger('id_gudang_banded');
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('depo');
    }
}

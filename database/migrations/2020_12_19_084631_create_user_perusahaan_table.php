<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserPerusahaanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_perusahaan', function (Blueprint $table) {
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('perusahaan_id');

            //FOREIGN KEY CONSTRAINTS
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('perusahaan_id')->references('id')->on('perusahaan')->onDelete('cascade');

            //SETTING THE PRIMARY KEYS
            $table->primary(['user_id','perusahaan_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_perusahaan');
    }
}

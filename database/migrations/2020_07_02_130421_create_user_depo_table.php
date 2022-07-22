<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserDepoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_depo', function (Blueprint $table) {
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('depo_id');
            
            //FOREIGN KEY CONSTRAINTS
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('depo_id')->references('id')->on('depo')->onDelete('cascade');

            //SETTING THE PRIMARY KEYS
            $table->primary(['user_id','depo_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_depo');
    }
}

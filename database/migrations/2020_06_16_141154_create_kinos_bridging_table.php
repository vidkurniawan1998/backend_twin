<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKinosBridgingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kino_bridging', function (Blueprint $table) {
            $table->increments('id');
            $table->string('cflag', 3);
            $table->string('cdist', 25);
            $table->string('ctran', 35);
            $table->date('dtran', 35)->nullable();
            $table->string('outlet', 35)->nullable();
            $table->string('csales', 35)->nullable();
            $table->string('ccompany', 25);
            $table->string('citem', 35);
            $table->string('cgudang1', 35);
            $table->string('cgudang2', 35);
            $table->string('ctypegd1', 25);
            $table->string('ctypegd2', 25);
            $table->float('njumlah', 20,2);
            $table->boolean('lbonus')->default(false);
            $table->string('unit', 25);
            $table->float('nisi', 10,2)->default(0);
            $table->float('nharga', 20,2)->default(0);
            $table->float('ndisc1', 10,2)->default(0);
            $table->float('ndisc2', 10,2)->default(0);
            $table->float('ndisc3', 10,2)->default(0);
            $table->float('ndisc4', 10,2)->default(0);
            $table->float('ndisc5', 10,2)->default(0);
            $table->float('ndisc6', 10,2)->default(0);
            $table->float('ndiscg1', 10,2)->default(0);
            $table->float('ndiscg2', 10,2)->default(0);
            $table->boolean('fppn', 10,2)->default(false);
            $table->float('netsales', 20,2)->default(0);
            $table->softDeletes();
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
        Schema::dropIfExists('kino_bridging');
    }
}

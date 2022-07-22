<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePiutangIntravisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('piutang_intravis', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_toko');
            $table->string('acc_no', 50);
            $table->date('trans_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('inv_no', 100);
            $table->integer('inv_total')->default(0);
            $table->integer('inv_sisa')->default(0);
            $table->string('team');
            $table->enum('status', ['lunas', 'belum lunas'])->default('belum lunas');
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
        Schema::dropIfExists('piutang_intravis');
    }
}

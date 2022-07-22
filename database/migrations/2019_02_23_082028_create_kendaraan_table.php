<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKendaraanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kendaraan', function (Blueprint $table) {
            $table->increments('id');
            $table->string('no_pol_kendaraan',11);
            $table->enum('jenis', ['truck', 'pickup', 'minibus', 'sepeda_motor']);
            $table->string('merk');
            $table->string('body_no',10)->nullable();
            $table->year('tahun')->nullable();
            $table->date('samsat')->nullable();
            $table->enum('peruntukan', ['delivery', 'canvass'])->nullable();
            $table->text('keterangan')->nullable();
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
        Schema::dropIfExists('kendaraan');
    }
}

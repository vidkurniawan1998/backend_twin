<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReturBarangTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('retur_barang', function (Blueprint $table) {
            $table->increments('id',1000000); //nomor Bon Barang Retur
            $table->unsignedInteger('id_salesman');
            $table->unsignedInteger('id_toko');
            // $table->string('id_penjualan')->nullable();
            $table->enum('tipe_retur', ['tukar_guling','potong_nota'])->default('tukar_guling');
            $table->enum('tipe_barang', ['bs','baik','sample'])->default('bs');
            $table->text('keterangan')->nullable();
            // $table->enum('status', ['waiting', 'approved', 'otw', 'delivered', 'finish','canceled'])->default('waiting');
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('detail_retur_barang', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_retur_barang');
            $table->unsignedInteger('id_barang');
            $table->enum('kategori_bs', ['kd','tk','kp'])->nullable();
            $table->date('expired_date')->nullable();
            $table->unsignedInteger('jml_pcs');
            $table->unsignedInteger('id_harga')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
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
        Schema::dropIfExists('retur_barang');
        Schema::dropIfExists('detail_retur_barang');
    }
}

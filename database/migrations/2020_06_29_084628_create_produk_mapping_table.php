<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProdukMappingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('produk_mapping', function (Blueprint $table) {
            $table->increments('id');
            $table->string('s_code', 35);
            $table->string('supp_code', 50);
            $table->string('isi', 10);
            $table->string('satuan', 10);
            $table->string('supp_name', 255);
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
        Schema::dropIfExists('produk_mapping');
    }
}

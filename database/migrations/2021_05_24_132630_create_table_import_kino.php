<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableImportKino extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('import_kino', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('txt_name', 100);
            $table->string('no_reff', 100);
            $table->string('cust_no', 100);
            $table->string('cust_no_to', 100);
            $table->string('sls_no', 100);
            $table->date('tanggal');
            $table->string('time_in', 30);
            $table->string('p_code', 30);
            $table->integer('qty');
            $table->decimal('sell_price', 12, 2)->default(0);
            $table->string('top', 20);
            $table->string('flag_noo', 10);
            $table->string('cabang', 50);
            $table->string('kode_diskon', 50);
            $table->decimal('diskon_percent', 5, 2)->default(0);
            $table->decimal('diskon_value', 12, 2)->default(0);
            $table->string('kode_promo', 50);
            $table->decimal('promo_value', 12, 2)->default(0);
            $table->decimal('promo_percent', 5, 2)->default(0);
            $table->string('flag_promo', 50);
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
        Schema::dropIfExists('import_kino');
    }
}

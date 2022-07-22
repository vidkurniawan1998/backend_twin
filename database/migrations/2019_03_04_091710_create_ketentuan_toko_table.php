
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKetentuanTokoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ketentuan_toko', function (Blueprint $table) {
            $table->unsignedInteger('id_toko')->primary();
            $table->enum('k_t', ['kredit', 'tunai'])->default('kredit')->nullable();
            // $table->unsignedSmallInteger('top')->default('14');
            $table->unsignedBigInteger('limit')->default('500000')->nullable();
            $table->enum('minggu', ['1&3', '2&4', '1-4'])->nullable();
            $table->enum('hari', ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'])->nullable();
            $table->string('id_tim',10)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ketentuan_toko');
    }
}

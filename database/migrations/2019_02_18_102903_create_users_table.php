<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email', 190)->unique();
            $table->string('password');
            $table->string('phone', 15)->nullable()->unique();
            $table->string('nik', 16)->nullable();
            $table->text('avatar')->nullable();
            $table->enum('status', ['active', 'non_active', 'need_activation'])->default('need_activation');
            $table->enum('role', ['pegawai', 'accounting', 'kepala_gudang', 'salesman', 'driver', 'logistik', 'pimpinan', 'admin']);
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
        Schema::dropIfExists('users');
    }
}

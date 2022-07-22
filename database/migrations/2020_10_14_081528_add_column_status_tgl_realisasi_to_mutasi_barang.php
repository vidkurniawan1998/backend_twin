<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\MutasiBarang;

class AddColumnStatusTglRealisasiToMutasiBarang extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mutasi_barang', function (Blueprint $table) {
            $table->enum('status', ['waiting', 'approved', 'on the way', 'received'])->default('waiting');
            $table->date('tanggal_realisasi')->nullable();
        });

        $mutasi_barang = MutasiBarang::all();

        foreach ($mutasi_barang as $mb) {
            if ($mb->is_approved == 1)
                $mb->status = 'received';

            $mb->tanggal_realisasi = $mb->tanggal_mutasi;
            $mb->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mutasi_barang', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('tanggal_realisasi');
        });
    }
}

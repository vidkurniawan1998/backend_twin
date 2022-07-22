<?php


use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class NoMutasiSeeder extends Seeder
{
    public function run()
    {
        DB::statement("UPDATE mutasi_barang SET no_mutasi = id WHERE is_approved=1");
    }
}
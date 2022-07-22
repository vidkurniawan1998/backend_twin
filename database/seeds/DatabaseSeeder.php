<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(LogStockSeeder::class);
        // $this->call(LogStockAwalSeeder::class);
        // $this->call(MutasiLogSeeder::class);

        $this->call(DeleteDuplicateLogStockSeeder::class);
    }
}

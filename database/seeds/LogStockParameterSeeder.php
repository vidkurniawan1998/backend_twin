<?php

use App\Models\Stock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\LogStock;

class LogStockParameterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        LogStock::whereIn('referensi',['penjualan','mutasi keluar'])->update(['parameter'=>(-1)]);
    }
}

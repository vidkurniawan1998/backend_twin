<?php

use Illuminate\Database\Seeder;
use App\Models\LogStock;

class DeleteDuplicateLogStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $double_loading = LogStock::where('referensi','penjualan')
                                        ->whereNull('deleted_at')
                                        ->where('status','loaded')
                                        ->groupBy('id_referensi')
                                        ->havingRaw("COUNT(id_referensi) > 1")
                                        ->pluck('id_referensi');
        echo "Loaded: ";
        echo "\r\n";
        foreach ($double_loading as $row) {
            LogStock::where('referensi','penjualan')
                        ->whereNull('deleted_at')
                        ->where('status','loaded')
                        ->where('id_referensi',$row)
                        ->first()
                        ->delete();
            echo $row;
            echo "\r\n";
        }
        
        $double_delivered = LogStock::where('referensi','penjualan')
                                        ->whereNull('deleted_at')
                                        ->where('status','delivered')
                                        ->groupBy('id_referensi')
                                        ->havingRaw("COUNT(id_referensi) > 1")
                                        ->pluck('id_referensi');
        echo "Delivered: ";
        echo "\r\n";                               
        foreach ($double_delivered as $row) {
            LogStock::where('referensi','penjualan')
                        ->whereNull('deleted_at')
                        ->where('status','delivered')
                        ->where('id_referensi',$row)
                        ->first()
                        ->delete();
            echo $row;
            echo "\r\n";
        }
        
        $double_mutasi_keluar = LogStock::where('referensi','mutasi keluar')
                                        ->whereNull('deleted_at')
                                        ->where('status','received')
                                        ->groupBy('id_referensi')
                                        ->havingRaw("COUNT(id_referensi) > 1")
                                        ->pluck('id_referensi');
        echo "Received: ";
        echo "\r\n";
        
        foreach ($double_mutasi_keluar as $row) {
            LogStock::where('referensi','mutasi keluar')
                        ->whereNull('deleted_at')
                        ->where('status','received')
                        ->where('id_referensi', $row)
                        ->first()
                        ->delete();
            echo $row;
            echo "\r\n";
        }
        
        $double_mutasi_masuk = LogStock::where('referensi','mutasi masuk')
                                        ->whereNull('deleted_at')
                                        ->where('status','received')
                                        ->groupBy('id_referensi')
                                        ->havingRaw("COUNT(id_referensi) > 1")
                                        ->pluck('id_referensi');
        echo "Received: ";
        echo "\r\n";
        
        foreach ($double_mutasi_masuk as $row) {
            LogStock::where('referensi','mutasi masuk')
                        ->whereNull('deleted_at')
                        ->where('status','received')
                        ->where('id_referensi', $row)
                        ->first()
                        ->delete();
            echo $row;
            echo "\r\n";
        }
        
        echo "Sukses Duplicate Data LogStock Penjualan Loaded\n";
    }
}

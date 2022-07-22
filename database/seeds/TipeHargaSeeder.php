<?php

use Illuminate\Database\Seeder;

class TipeHargaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'tipe_harga' => 'rbp',
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => 1
            ],
            [
                'tipe_harga' => 'hcobp',
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => 1
            ],
            [
                'tipe_harga' => 'wbp',
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => 1
            ],
            [
                'tipe_harga' => 'cbp',
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => 1
            ],
            [
                'tipe_harga' => 'dbp',
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => 1
            ]
        ];

        DB::table('tipe_harga')->insert($data);
    }
}
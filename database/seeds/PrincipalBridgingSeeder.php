<?php

use Illuminate\Database\Seeder;

class PrincipalBridgingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $principal = [
            [
                'kode' => 'C-004',
                'name' => 'DAPUR KITA',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'deleted_at' => NULL
            ],
            [
                'kode' => 'C-006',
                'name' => 'PT SIDOARJO',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'deleted_at' => NULL
            ],
            [
                'kode' => 'P-016',
                'name' => 'PT MULTI SARI SEDAP',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'deleted_at' => NULL
            ],
            [
                'kode' => 'P-020',
                'name' => 'PT ANUGRAH PERSADA ALAM',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'deleted_at' => NULL
            ],
            [
                'kode' => 'P-002',
                'name' => 'RECKIT',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'deleted_at' => NULL
            ],
        ];

        DB::table('principal_bridging')->insert($principal);
    }
}

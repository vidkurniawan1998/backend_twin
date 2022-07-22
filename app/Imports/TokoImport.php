<?php

namespace App\Imports;

use App\Models\Toko;
// use App\Models\KetentuanToko;
use Maatwebsite\Excel\Concerns\ToModel;

class TokoImport implements ToModel
{
    public function model(array $row)
    {
        return new Toko([
            'id' => $row[0],
            'nama_toko' => $row[3],
            'no_acc' => $row[1],
            'cust_no' => $row[2],
            'tipe' => $row[4],
            'tipe_harga' => strtolower($row[5]),
            'pemilik' => $row[6],
            'telepon' => $row[7],
            'alamat' => $row[8],
            'kode_pos' => $row[9],
            'id_kelurahan' => $row[10],
            'id_depo' => $row[11],
            'id_principal' => $row[25] ?? '',
            'kode_eksklusif' => $row[26] ?? '',
            'tipe_2' => $row[27] ?? '',
            'id_mitra' => $row[28] ?? null
        ]);
    }
}

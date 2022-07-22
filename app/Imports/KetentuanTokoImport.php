<?php

namespace App\Imports;

// use App\Models\Toko;
use App\Models\KetentuanToko;
use Maatwebsite\Excel\Concerns\ToModel;

class KetentuanTokoImport implements ToModel
{
    public function model(array $row)
    {
        return new KetentuanToko([
           'id_toko'    => $row[0],
           'k_t'        => $row[13],
           'top'        => $row[14] == '' ? 0:$row[14],
           'limit'      => $row[15],
           'minggu'     => $row[20],
           'hari'       => strtolower($row[16]),
           'npwp'       => $row[17],
           'nama_pkp'   => $row[18],
           'alamat_pkp' => $row[19],
           'id_tim'     => $row[21],
           'no_ktp'     => $row[22] ?? '',
           'nama_ktp'   => $row[23] ?? '',
           'alamat_ktp' => $row[24] ?? '',
        ]);
    }
}

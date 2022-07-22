<?php


namespace App\Imports;

use App\Models\HargaBarang;
use App\Models\HargaBarangAktif;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;

class HargaBarangImport implements ToModel
{
    public function model(array $row)
    {
        $ppn = 0.1;
        $harga[] = [
            'id_barang'     => $row[0],
            'tipe_harga'    => 'mt',
            'ppn'           => 100*$ppn,
            'harga'         => $row[1],
            'harga_non_ppn' => $row[1]/(1+$ppn),
            'ppn_value'     => ($row[1]/(1+$ppn))*$ppn,
            'created_by'    => 1
        ];

        $harga[] = [
            'id_barang'     => $row[0],
            'tipe_harga'    => 'gt',
            'ppn'           => 100*$ppn,
            'harga'         => $row[2],
            'harga_non_ppn' => $row[2]/(1+$ppn),
            'ppn_value'     => ($row[2]/(1+$ppn))*$ppn,
            'created_by'    => 1
        ];

        foreach ($harga as $hrg) {
            $hargaBarang = HargaBarang::create($hrg);
            if($hargaBarang->wasRecentlyCreated){
                $hrg['id_harga_barang'] = $hargaBarang->id;
                HargaBarangAktif::updateOrCreate(['id_barang' => $hrg['id_barang'], 'tipe_harga' => $hrg['tipe_harga']],$hrg)->save();
            }
        }
    }
}

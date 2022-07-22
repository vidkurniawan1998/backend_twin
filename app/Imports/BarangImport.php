<?php


namespace App\Imports;

use App\Models\Barang;
use Maatwebsite\Excel\Concerns\ToModel;

class BarangImport implements ToModel
{
    public function model(Array $row)
    {
        return new Barang([
            'id'            => $row[0],
            'kode_barang'   => $row[1],
            'item_code'     => $row[2],
            'barcode'       => $row[3],
            'nama_barang'   => $row[4],
            'berat'         => $row[5],
            'isi'           => $row[6],
            'satuan'        => $row[7],
            'id_segmen'     => $row[8],
            'id_perusahaan' => $row[9],
            'id_mitra'      => $row[10] ?? null,
            'extra'         => $row[11],
            'status'        => $row[12]
        ]);
    }
}

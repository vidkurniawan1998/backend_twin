<?php


namespace App\Imports;


use App\Models\HargaBarang;
use Maatwebsite\Excel\Concerns\ToModel;

class UpdateHargaBarangImport implements ToModel
{
    public function model(array $row)
    {
        $id_barang = trim($row[0]);
        if ($id_barang) {
            $harga_barang = HargaBarang::where('id_barang', $id_barang)
                ->update(['harga' => $row[2]]);
            return null;
        }
    }
}
<?php


namespace App\Imports;


use App\Models\Barang;
use Maatwebsite\Excel\Concerns\ToModel;

class UpdateBarangImport implements ToModel
{
    public function model(array $row)
    {
        $id_barang = $row[0];
        $kode_barang = $row[1];
        $nama_barang = $row[2];
        $isi = $row[3];

        $barang = Barang::find($id_barang);
        $barang->kode_barang = $kode_barang;
        $barang->nama_barang = $nama_barang;
        $barang->isi = $isi;
        $barang->save();
        return null;
    }
}

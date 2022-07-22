<?php


namespace App\Imports;


use App\Models\Toko;
use App\Models\TokoNoLimit;
use Maatwebsite\Excel\Concerns\ToModel;

class LockOrderOutletImport implements ToModel
{
    public function model(array $row)
    {
        $id_toko        = trim($row[0]);
        $id_depo        = $row[1];
        $tipe           = $row[2];

        $toko = Toko::where('no_acc', $id_toko)
            ->where('id_depo', $id_depo)
            ->first();

        if (!$toko) {
            return null;
        }

        $data = [
            'id_toko'   => $toko->id,
            'created_by'=> 1,
            'tipe'      => $tipe
        ];

        return TokoNoLimit::create($data);
    }
}
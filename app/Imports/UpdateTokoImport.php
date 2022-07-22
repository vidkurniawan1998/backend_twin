<?php


namespace App\Imports;


use App\Models\Toko;
use Maatwebsite\Excel\Concerns\ToModel;

class UpdateTokoImport implements ToModel
{
    public function model(array $row)
    {
        $id_toko        = trim($row[0]);
        $id_depo        = $row[1];
        $tipe_import    = $row[2];

        $toko = Toko::where('id', $id_toko)
                ->where('id_depo', $id_depo)
                ->first();

        if (!$toko) {
            return null;
        }

        if ($tipe_import === 'limit kredit') {
            $ketentuan_toko = $toko->ketentuan_toko;
//            $toko->id_depo = $id_depo;
//            $toko->save();
            $ketentuan_toko->limit = $row[3];
            if (isset($row[4]) && $row[4] !== '') {
                $ketentuan_toko->top = $row[4];
            }

//            if (isset($row[5]) && $row[5] !== '') {
//                $ketentuan_toko->k_t = $row[5];
//            }
//
//            if (isset($row[6]) && $row[6] !== '') {
//                $ketentuan_toko->id_tim = $row[6];
//            }

            $ketentuan_toko->save();
            return null;
        }

        if ($tipe_import === 'ktp') {
            $ketentuan_toko = $toko->ketentuan_toko;
            $ketentuan_toko->no_ktp     = $row[3];
            $ketentuan_toko->nama_ktp   = $row[4];
            $ketentuan_toko->alamat_ktp = $row[5];
            $ketentuan_toko->save();
            return null;
        }

        if ($tipe_import === 'npwp') {
            $ketentuan_toko = $toko->ketentuan_toko;
            $ketentuan_toko->npwp       = $row[3];
            $ketentuan_toko->nama_pkp   = $row[4];
            $ketentuan_toko->alamat_pkp = $row[5];
            $ketentuan_toko->save();
            return null;
        }

        if ($tipe_import === 'tim') {
            $ketentuan_toko = $toko->ketentuan_toko;
            $ketentuan_toko->id_tim     = $row[3];
            // $ketentuan_toko->hari       = $row[4];
            // $ketentuan_toko->minggu     = $row[5];
            $ketentuan_toko->save();
            return null;
        }

        if($tipe_import === 'depo'){
            $toko->id_depo = $row[3];
            $toko->save();
            return null;
        }

        if ($tipe_import === 'hari') {
            $ketentuan_toko = $toko->ketentuan_toko;
            $ketentuan_toko->hari   = $row[3];
            $ketentuan_toko->minggu = $row[4];
            $ketentuan_toko->save();
            return null;
        }
    }
}

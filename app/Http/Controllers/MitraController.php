<?php


namespace App\Http\Controllers;


use App\Http\Resources\MitraSimple as MitraSimpleResources;
use App\Models\Mitra;

class MitraController extends Controller
{
    public function list()
    {
        $data = Mitra::select('id', 'kode_mitra', 'perusahaan')->get();
        return MitraSimpleResources::collection($data);
    }
}

<?php

namespace App\Services;

use App\Models\Provinsi;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use Illuminate\Http\Request;

use App\Http\Resources\Provinsi as ProvinsiResource;
use App\Http\Resources\Kabupaten as KabupatenResource;
use App\Http\Resources\Kecamatan as KecamatanResource;
use App\Http\Resources\Kelurahan as KelurahanResource;


class DaerahService
{
    public function list($type, $parentId = null)
    {
        switch ($type) {
            case 'provinsi':
                // $items = Provinsi::get();
                $list_provinsi = Provinsi::get();
                $items = ProvinsiResource::collection($list_provinsi);
                break;

            case 'kabupaten': 
                // $items = Kabupaten::get();
                $list_kabupaten = Kabupaten::orderBy('nama_kabupaten')->get();
                $items = KabupatenResource::collection($list_kabupaten);
                break;

            case 'kecamatan':
                // $items = Kecamatan::get();
                $list_kecamatan = Kecamatan::where('id_kabupaten', $parentId)->orderBy('nama_kecamatan')->get();
                $items = KecamatanResource::collection($list_kecamatan);
                break;

            case 'kelurahan':
                // $items = Kelurahan::get();
                $list_kelurahan = Kelurahan::where('id_kecamatan', $parentId)->orderBy('nama_kelurahan')->get();
                $items = KelurahanResource::collection($list_kelurahan);
                break;

            default:
                // $items = Provinsi::get();
                $list_provinsi = Provinsi::get();
                $items = ProvinsiResource::collection($list_provinsi);
                break;
        }

        return $items;
    }
}
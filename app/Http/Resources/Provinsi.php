<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class Provinsi extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'nama_provinsi' => $this->nama_provinsi,
            // 'banyak_kabupaten' => $this->kabupaten->count(),
            // 'banyak_kecamatan' => $this->kabupaten->kecamatan->count(),
            // 'banyak_kelurahan' => $this->kabupaten->kecamatan->kelurahan->count(),
        ];
    }
}

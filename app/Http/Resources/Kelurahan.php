<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Kelurahan extends JsonResource
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
            'nama_kelurahan' => $this->nama_kelurahan,

            // 'id_provinsi' => $this->kecamatan->kabupaten->id_provinsi,
            'nama_provinsi' => $this->kecamatan->kabupaten->provinsi->nama_provinsi,
            // 'id_kabupaten' => $this->kecamatan->id_kabupaten,
            'nama_kabupaten' => $this->kecamatan->kabupaten->nama_kabupaten,
            // 'id_kecamatan' => $this->id_kecamatan,
            'nama_kecamatan' => $this->kecamatan->nama_kecamatan,
        ];
    }
}

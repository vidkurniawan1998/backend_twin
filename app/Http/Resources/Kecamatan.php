<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Kecamatan extends JsonResource
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
            'nama_kecamatan' => $this->nama_kecamatan,
            // 'banyak_kelurahan' => $this->kelurahan->count(),

            // 'id_provinsi' => $this->kabupaten->id_provinsi,
            'nama_provinsi' => $this->kabupaten->provinsi->nama_provinsi,
            // 'id_kabupaten' => $this->id_kabupaten,
            'nama_kabupaten' => $this->kabupaten->nama_kabupaten,
            // 'banyak_kelurahan' => $this->kelurahan->count(),
        ];
    }
}

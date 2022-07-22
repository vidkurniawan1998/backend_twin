<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Kabupaten extends JsonResource
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
            'nama_kabupaten' => $this->nama_kabupaten,
            // 'banyak_kecamatan' => $this->kecamatan->count(),

            // 'id_provinsi' => $this->id_provinsi,
            'nama_provinsi' => $this->provinsi->nama_provinsi,
            // 'banyak_kelurahan' => $this->kecamatan->kelurahan->count(),
        ];
    }
}

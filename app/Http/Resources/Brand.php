<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Brand extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $perusahaan = $this->principal->perusahaan;
        return [
            'id' => $this->id,
            'nama_brand' => $this->nama_brand,
            'id_principal' => $this->id_principal,
            'nama_principal' => $this->nama_principal,
            'kode_perusahaan' => $perusahaan->kode_perusahaan,
            'nama_perusahaan' => $perusahaan->nama_perusahaan,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            // 'deleted_by' => $this->deleted_by,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
            // 'deleted_at' => (string) $this->deleted_at
        ];
    }
}

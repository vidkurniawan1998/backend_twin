<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class KepalaGudang extends Resource
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
            'id' => $this->user_id,
            'id_gudang' => $this->id_gudang,
            'nama_kepala_gudang' => $this->nama_kepala_gudang,
            'nama_gudang' => $this->nama_gudang,
            'jenis_gudang' => $this->jenis_gudang,
            'keterangan_gudang' => $this->keterangan_gudang,
        ];
    }
}
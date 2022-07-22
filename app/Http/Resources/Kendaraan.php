<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class Kendaraan extends Resource
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
            'no_pol_kendaraan' => $this->no_pol_kendaraan,
            'jenis' => $this->jenis,
            'merk' => $this->merk,
            'body_no' => $this->body_no,
            'tahun' => $this->tahun,
            'samsat' => $this->samsat,
            'peruntukan' => $this->peruntukan,
            'keterangan' => $this->keterangan,
            
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            // 'deleted_by' => $this->deleted_by,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
            // 'deleted_at' => (string) $this->deleted_at
        ];
    }
}

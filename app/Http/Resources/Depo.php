<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use function foo\func;

class Depo extends JsonResource
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
            'kode_depo' => $this->kode_depo,
            'nama_depo' => $this->nama_depo,
            'id_gudang' => $this->id_gudang,
            'nama_gudang' => $this->nama_gudang,
            'alamat' => $this->alamat,
            'telp' => $this->telp,
            'fax' => $this->fax,
            'kabupaten'=> $this->kabupaten,

            'id_gudang_bs' => $this->id_gudang_bs,
            'nama_gudang_bs' => $this->nama_gudang_bs,

            'id_gudang_tg' => $this->id_gudang,
            'nama_gudang_tg' => $this->nama_gudang_tg,

            'id_gudang_banded' => $this->id_gudang_banded,
            'nama_gudang_banded' => $this->nama_gudang_banded,

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            // 'deleted_by' => $this->deleted_by,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
            // 'deleted_at' => (string) $this->deleted_at
            'perusahaan' => new Perusahaan($this->whenLoaded('perusahaan'))
        ];
    }
}

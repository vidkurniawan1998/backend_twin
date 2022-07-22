<?php


namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class Principal extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'nama_principal' => $this->nama_principal,
            'alamat' => $this->alamat,
            'kode_pos' => $this->kode_pos,
            'telp' => $this->telp,
            'perusahaan' => new Perusahaan($this->whenLoaded('perusahaan'))
        ];
    }
}

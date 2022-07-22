<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class HargaBarang extends JsonResource
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
            'id_barang' => $this->id_barang,
            'tipe_harga' => $this->tipe_harga,
            'harga' => $this->harga,

            'nama_barang' => $this->barang->nama_barang,
            'kode_barang' => $this->barang->kode_barang,

            'created_by' => $this->created_by,
            // 'updated_by' => $this->updated_by,
            // 'deleted_by' => $this->deleted_by,
            'created_at' => Carbon::parse($this->created_at)->format('Y-m-d'),
            // 'updated_at' => (string) $this->updated_at,
            // 'deleted_at' => (string) $this->deleted_at
        ];
    }
}

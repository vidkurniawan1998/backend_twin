<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockOpname extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'tanggal_so' => $this->tanggal_so,
            'id_gudang' => $this->id_gudang,
            'nama_gudang' => $this->nama_gudang,
            'is_approved' => $this->is_approved,
            'keterangan' => $this->keterangan,
        ];
    }
}

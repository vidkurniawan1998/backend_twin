<?php


namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class PromoBarang extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'kode_barang'   => $this->kode_barang,
            'nama_barang'   => $this->nama_barang,
            'volume'        => $this->pivot->volume,
            'bonus_pcs'     => $this->pivot->bonus_pcs
        ];
    }
}

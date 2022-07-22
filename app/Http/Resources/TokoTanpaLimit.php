<?php


namespace App\Http\Resources;


use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class TokoTanpaLimit extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'        => $this->id,
            'no_acc'    => $this->toko->no_acc,
            'toko'      => $this->id_toko,
            'nama_toko' => $this->toko->nama_toko,
            'depo'      => $this->toko->depo->nama_depo,
            'tipe'      => $this->tipe,
            'created_at'=> Carbon::parse($this->created_at)->diffForHumans(),
        ];
    }
}

<?php


namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class SalesmanSimple extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id_depo'       => $this->tim->id_depo,
            'id_user'       => $this->user_id,
            'nama_tim'      => $this->tim->nama_tim,
            'nama'          => $this->user->name,
            'nama_depo'     => $this->tim->depo->nama_depo,
            'target'        => 0,
            'kode_eksklusif'=> $this->kode_eksklusif,
        ];
    }
}

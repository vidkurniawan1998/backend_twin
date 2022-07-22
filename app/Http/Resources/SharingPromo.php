<?php


namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class SharingPromo extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'id_promo'          => $this->id_promo,
            'nama_promo'        => $this->whenLoaded('promo')->nama_promo,
            'proposal'          => $this->whenLoaded('promo')->no_promo ?? '',
            'persen_dist'       => $this->persen_dist,
            'persen_principal'  => $this->persen_principal,
            'nominal_dist'      => $this->nominal_dist,
            'nominal_principal' => $this->nominal_principal,
            'extra_dist'        => $this->extra_dist,
            'extra_principal'   => $this->extra_principal,
        ];
    }
}
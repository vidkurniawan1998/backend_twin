<?php


namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class GrupTokoLogistik extends JsonResource
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
            'id'        => $this->id,
            'nama_grup' => $this->nama_grup,
            'toko'      => TokoListSimple::collection($this->whenLoaded('toko'))
        ];
    }

}
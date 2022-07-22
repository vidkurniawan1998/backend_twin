<?php


namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class MitraSimple extends JsonResource
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
            'kode_mitra' => $this->kode_mitra,
            'perusahaan' => $this->perusahaan
        ];
    }
}

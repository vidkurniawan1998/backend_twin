<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use \Carbon\Carbon as Carbon;

class TipeHarga extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'tipe_harga' => strtoupper($this->tipe_harga),
            'created_at' => Carbon::parse($this->created_at)->diffForHumans(),
            'perusahaan' => Perusahaan::collection($this->whenLoaded('perusahaan'))
        ];
    }
}

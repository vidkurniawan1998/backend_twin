<?php


namespace App\Http\Resources;


use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ListHargaBarang extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'        => $this->id,
            'harga'     => $this->harga / 1.1,
            'tanggal'   => Carbon::parse($this->created_at)->format('Y-m-d')
        ];
    }
}

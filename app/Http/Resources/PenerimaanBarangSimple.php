<?php


namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class PenerimaanBarangSimple extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'no_pb'         => $this->no_pb,
            'no_do'         => $this->no_do,
            'tgl_bongkar'   => $this->tgl_bongkar
        ];
    }
}

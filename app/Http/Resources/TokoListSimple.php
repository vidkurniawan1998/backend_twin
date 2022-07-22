<?php


namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class TokoListSimple extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'        => $this->id,
            'no_acc'    => $this->no_acc,
            'cust_no'   => $this->cust_no,
            'nama_toko' => $this->nama_toko,
            'nama_tim'  => $this->nama_tim ?? '',
            'alamat'    => $this->alamat,
            'kelurahan' => $this->whenLoaded('kelurahan', function () {
                return $this->kelurahan->nama_kelurahan;
            }),
            'perusahaan' => $this->whenLoaded('depo', function () {
                return $this->depo->perusahaan->kode_perusahaan;
            }),
        ];
    }
}

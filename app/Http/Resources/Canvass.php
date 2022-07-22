<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Canvass extends JsonResource
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
            'id_tim' => $this->id_tim,
            'id_gudang_canvass' => $this->id_gudang_canvass,
            'id_kendaraan' => $this->id_kendaraan,
            'nama_tim' => $this->nama_tim,
            'nama_sales' => $this->name,
            'nama_depo' => $this->nama_depo,
            'nama_gudang' => $this->nama_gudang,
            'kendaraan' => $this->no_pol_kendaraan,
            'body_no' => $this->body_no,
            'kode_perusahaan' => $this->kode_perusahaan,

            // 'id_gudang_canvass' => $this->id_gudang_canvass,
            // 'id_kendaraan' => $this->id_kendaraan,
            // 'id_tim' => $this->id_tim,
            // 'no_pol_kendaraan' => $this->no_pol_kendaraan,
            // 'body_no' => $this->body_no,
            // 'nama_tim' => $this->nama_tim,
            // 'nama_driver' => $this->nama_driver,
            // 'nama_salesman' => $this->nama_salesman,
        ];
    }
}

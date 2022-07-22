<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Driver extends JsonResource
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
            'id' => $this->user_id,
            // 'nama_driver' => $this->whenLoaded('user')->name,
            // 'email' => $this->whenLoaded('user')->email,
            // 'phone' => $this->whenLoaded('user')->phone

            'nama_driver' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'jumlah_invoice' => $this->jumlah_invoice,
            'kode_perusahaan' => $this->kode_perusahaan,
            'nama_perusahaan' => $this->nama_perusahaan
        ];
    }
}

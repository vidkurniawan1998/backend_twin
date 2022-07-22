<?php

namespace App\Http\Resources;

use Carbon\Carbon as Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class KunjunganSales extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request):array
    {
        return [
            'id'        => $this->id,
            'id_toko'   => $this->whenLoaded('toko')->id,
            'nama_toko' => $this->whenLoaded('toko')->nama_toko,
            'no_acc'    => $this->whenLoaded('toko')->no_acc,
            'cust_no'   => $this->whenLoaded('toko')->cust_no,
            'alamat'    => $this->whenLoaded('toko')->alamat,
            'status'    => $this->status,
            'latitude'  => $this->latitude,
            'longitude' => $this->longitude,
            'status'    => $this->status,
            'keterangan'=> $this->keterangan,
            'salesman'  => $this->whenLoaded('user')->name,
            'tim'       => $this->whenLoaded('user', function () {
                return $this->user->salesman->tim->nama_tim ?? '';
            }),
            'tanggal'   => Carbon::parse($this->created_at)->format('Y-m-d')
        ];
    }
}

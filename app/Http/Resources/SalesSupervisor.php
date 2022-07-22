<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class SalesSupervisor extends Resource
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
            'nama_sales_supervisor' => $this->user->name,
            'keterangan' => $this->keterangan,
        ];
    }
}
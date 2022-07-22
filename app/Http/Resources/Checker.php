<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Checker extends JsonResource
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
            'nama_checker' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }
}

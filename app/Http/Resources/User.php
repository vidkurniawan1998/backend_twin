<?php


namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class User extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'email'     => $this->email,
            'phone'     => $this->phone,
            'nik'       => $this->nik,
            'perusahaan'=> Perusahaan::collection($this->whenLoaded('perusahaan')),
            'roles'     => $this->whenLoaded('roles'),
            'depo'      => Depo::collection($this->whenLoaded('depo')),
            'gudang'    => Gudang::collection($this->whenLoaded('gudang')),
            'status'    => $this->status
        ];
    }
}

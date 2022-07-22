<?php


namespace App\Http\Resources;


use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class Perusahaan extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'kode_perusahaan'   => $this->kode_perusahaan,
            'nama_perusahaan'   => $this->nama_perusahaan,
            'npwp'              => $this->npwp,
            'nama_pkp'          => $this->nama_pkp,
            'alamat_pkp'        => $this->alamat_pkp,
            'created_at'        => Carbon::parse($this->created_at)->diffForHumans()
        ];
    }
}

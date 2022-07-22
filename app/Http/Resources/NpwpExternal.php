<?php


namespace App\Http\Resources;


use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class NpwpExternal extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'kode_outlet'   => $this->kode_outlet,
            'nama_toko'     => $this->nama_toko,
            'npwp'          => $this->npwp,
            'nama_pkp'      => $this->nama_pkp,
            'alamat_pkp'    => $this->alamat_pkp,
        ];
    }
}

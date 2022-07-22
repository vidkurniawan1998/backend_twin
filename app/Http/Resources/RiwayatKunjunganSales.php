<?php

namespace App\Http\Resources;

use Carbon\Carbon as Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class RiwayatKunjunganSales extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        Carbon::setLocale('id');
        return [
            'tanggal'           => $this->tanggal,
            'tanggal_format'    => Carbon::parse($this->tanggal)->translatedFormat('j F Y'),
            'jumlah_kunjungan'  => $this->jumlah_kunjungan
        ];
    }
}

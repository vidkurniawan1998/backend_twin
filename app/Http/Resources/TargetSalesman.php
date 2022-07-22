<?php


namespace App\Http\Resources;


use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class TargetSalesman extends JsonResource
{
    public function toArray($request)
    {
        Carbon::setLocale('id');
        return [
            'id'                => $this->id,
            'id_perusahaan'     => $this->id_perusahaan,
            'kode_perusahaan'   => $this->perusahaan->kode_perusahaan,
            'nama_perusahaan'   => $this->perusahaan->nama_perusahaan,
            'id_depo'           => $this->id_depo,
            'id_user'           => $this->id_user,
            'nama_salesman'     => $this->user->name,
            'tim'               => $this->salesman->tim->nama_tim,
            'nama_depo'         => $this->depo->nama_depo,
            'periode'           => Carbon::parse($this->mulai_tanggal)->translatedFormat('F Y'),
            'mulai_tanggal'     => $this->mulai_tanggal,
            'sampai_tanggal'    => $this->sampai_tanggal,
            'hari_kerja'        => $this->hari_kerja,
            'target'            => $this->target
        ];
    }
}

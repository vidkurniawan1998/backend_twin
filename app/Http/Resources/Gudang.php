<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Gudang extends JsonResource
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
            'id'                    => $this->id,
            'nama_gudang'           => $this->nama_gudang,
            'kode_gudang'           => $this->kode_gudang,
            'jenis'                 => $this->jenis,
            'keterangan'            => $this->keterangan,
            'banyak_jenis_barang'   => $this->stock->count(),
            'qty'                   => $this->stock->sum('qty'),
            'qty_pcs'               => $this->stock->sum('qty_pcs'),
            'created_by'            => $this->created_by,
            'updated_by'            => $this->updated_by,
            'created_at'            => (string) $this->created_at,
            'updated_at'            => (string) $this->updated_at,
            'id_depo'               => $this->depo->id,
            'nama_depo'             => $this->depo->nama_depo,
            'alamat_depo'           => $this->depo->alamat,
            'telp'                  => $this->depo->telp,
            'fax'                   => $this->depo->fax,
            'kabupaten'             => $this->depo->kabupaten,
            'id_perusahaan'         => $this->depo->perusahaan->id,
            'kode_perusahaan'       => $this->depo->perusahaan->kode_perusahaan,
            'nama_perusahaan'       => $this->depo->perusahaan->nama_perusahaan,
        ];
    }
}

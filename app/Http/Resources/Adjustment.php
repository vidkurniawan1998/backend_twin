<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Adjustment extends JsonResource
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
            'id'            => $this->id,
            'no_adjustment' => $this->no_adjustment,
            'id_gudang'     => $this->id_gudang,
            'nama_gudang'   => $this->gudang->nama_gudang,
            'tanggal'       => $this->tanggal,
            'status'        => $this->status,
            'keterangan'    => $this->keterangan,
            'pic'           => $this->pic->name,
            'depo'          => $this->gudang->depo,
            'perusahaan'    => $this->gudang->depo->perusahaan,
            'created_by'    => $this->created_by,
            'updated_by'    => $this->updated_by,
            // 'deleted_by' => $this->deleted_by,
            'created_at'    => (string) $this->created_at,
            'updated_at'    => (string) $this->updated_at,
            // 'deleted_at' => (string) $this->deleted_at,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Tim extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $depo = $this->depo;
        return [
            'id' => $this->id,
            'nama_tim' => $this->nama_tim,
            'tipe' => $this->tipe,
            'id_gudang' => $this->id_gudang,
            'nama_gudang' => $this->depo->gudang->nama_gudang,
            'id_depo' => $this->id_depo,
            'nama_depo' => $depo->nama_depo,
            'id_perusahaan' => $depo->perusahaan->id_perusahaan,
            'kode_perusahaan' => $depo->perusahaan->kode_perusahaan,
            'nama_perusahaan' => $depo->perusahaan->nama_perusahaan,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            // 'deleted_by' => $this->deleted_by,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
            // 'deleted_at' => (string) $this->deleted_at,
            'id_sales_koordinator' => $this->id_sales_koordinator,
            'id_sales_supervisor' => $this->id_sales_supervisor,
            'nama_koordinator' => $this->id_sales_koordinator != null ? $this->user_koordinator->name:'Kosong',
            'nama_supervisor' => $this->id_sales_supervisor != null ? $this->user_supervisor->name:'Kosong',
        ];
    }
}

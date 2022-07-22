<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class Promo2 extends Resource
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
            // 'kode_promo' => $this->kode_promo,
            'nama_promo' => $this->nama_promo,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'min_qty_dus' => $this->min_qty_dus,
            'keterangan' => $this->keterangan,
            'disc_persen' => $this->disc_persen,
            'disc_rupiah' => $this->disc_rupiah,

            // barang EXTRA
            'id_barang_extra' => $this->id_barang,
            'pcs_extra' => $this->pcs_extra,
            'nama_barang_extra' => $this->pcs_extra ? $this->barang->nama_barang : null,

            // SYARAT PROMO
            // DEPO
            'list_depo' => $this->whenLoaded('promo_depo'),

            // TOKO
            'list_toko' => $this->whenLoaded('promo_toko'),
            
            // BARANG
            'list_barang' => $this->whenLoaded('promo_barang'),
            
            // 'created_by' => $this->created_by,
            // 'updated_by' => $this->updated_by,
            // 'deleted_by' => $this->deleted_by,
            // 'created_at' => (string) $this->created_at,
            // 'updated_at' => (string) $this->updated_at,
            // 'deleted_at' => (string) $this->deleted_at
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DetailAdjustment extends JsonResource
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
            'id_adjustment' => $this->id_adjustment,

            'id_stock' => $this->id_stock,
            'nama_barang' => strtoupper($this->nama_barang),
            'kode_barang' => $this->kode_barang,
            'qty_adj' => $this->qty_adj,
            'pcs_adj' => $this->pcs_adj,
            'satuan' => $this->satuan,

            'id_harga' => $this->id_harga,
            'tipe_harga' => $this->tipe_harga,
            'price_before_tax' => $this->price_before_tax,
            'subtotal' => $this->subtotal,

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            // 'deleted_by' => $this->deleted_by,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
            // 'deleted_at' => (string) $this->deleted_at,
        ];
    }
}

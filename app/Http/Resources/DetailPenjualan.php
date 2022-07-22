<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DetailPenjualan extends JsonResource
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
            'id_penjualan' => $this->id_penjualan,
            'id_stock' => $this->id_stock,
            'qty' => $this->qty,
            'qty_pcs' => $this->qty_pcs,
            'order_qty' => $this->order_qty,
            'order_pcs' => $this->order_pcs,
            'id_promo' => $this->id_promo,

            'id_barang' => $this->id_barang,
            'kode_barang' => $this->kode_barang,
            'nama_barang' => $this->nama_barang,
            'satuan' => $this->stock->barang->satuan,

            'status' => $this->status,

            'id_harga' => $this->id_harga,
            'tipe_harga' => $this->harga_barang->tipe_harga,
            // 'harga_barang' => (int)($this->harga_barang->harga / 1.1), // price_before_tax =  price_after_tax / 1.1;
            'harga_barang' => round($this->price_before_tax,2),
            'price_after_tax' => round($this->harga_jual,2),

            'nama_promo' => $this->nama_promo,

            'subtotal' => round($this->subtotal,2),
            'subtotal_after_tax' => round($this->subtotal_after_tax,2),
            'discount' => round($this->discount,2),
            'net' => round($this->net,2),
            'dpp' => round($this->dpp,2),

            'created_by' => $this->created_by,
            // 'updated_by' => $this->updated_by,
            'created_at' => (string) $this->created_at,
            // 'updated_at' => (string) $this->updated_at
            'qty_pcs_loading' => $this->qty_pcs_loading,
            'qty_loading' => $this->qty_loading,
        ];
    }
}

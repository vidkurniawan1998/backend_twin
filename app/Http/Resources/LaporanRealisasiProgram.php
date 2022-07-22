<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class LaporanRealisasiProgram extends Resource
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
            'no_po' => $this->id_penjualan,
            'no_invoice' => $this->penjualan->no_invoice,
            'qty' => $this->qty,
            'qty_pcs' => $this->qty_pcs,
            'kode_barang' => $this->kode_barang,
            'nama_barang' => $this->nama_barang,
            'nama_promo' => $this->nama_promo,
            'discount' => round($this->discount,2),
            'ppn' => round($this->discount * 0.1,2),
            'discount_inc_tax' => round($this->discount * 1.1,2),
            'nama_tim' => $this->penjualan->nama_tim,
            'no_acc' => $this->penjualan->toko->no_acc,
            'cust_no' => $this->penjualan->toko->cust_no,
            'nama_toko' => $this->penjualan->toko->nama_toko,
            'alamat' => $this->penjualan->toko->alamat,
            'created_at' => (string) $this->penjualan->created_at,
            'approved_at' => (string) $this->penjualan->approved_at,
            'delivered_at' => (string) $this->penjualan->delivered_at,
        ];
    }
}

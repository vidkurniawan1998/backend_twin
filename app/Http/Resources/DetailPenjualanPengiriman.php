<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class DetailPenjualanPengiriman extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if($this->harga_barang->id == 0){
            $nama_barang = '[EXTRA] ' . $this->nama_barang;
        }
        else{
            $nama_barang = $this->nama_barang;
        }

        if ($this->id_promo) {
            $nama_promo = $this->promo->nama_promo;
        }
        else {
            $nama_promo = null;
        }

        return [
            'id' => $this->id,
            'id_penjualan' => $this->id_penjualan,
            'id_stock' => $this->id_stock,
            'qty' => $this->qty,
            'qty_pcs' => $this->qty_pcs,
            // 'qty_available' => $this->qty_available,
            // 'id_promo' => $this->id_promo,
            
            'id_barang' => $this->id_barang,
            'kode_barang' => $this->kode_barang,
            'nama_barang' => $nama_barang,
            'satuan' => $this->stock->barang->satuan,

            // 'status' => $this->status,

            // 'id_harga' => $this->id_harga,
            // 'tipe_harga' => $this->harga_barang->tipe_harga,
            // 'harga_barang' => (int)($this->harga_barang->harga / 1.1), // price_before_tax =  price_after_tax / 1.1;

            // 'nama_promo' => $nama_promo,

            // 'subtotal' => $this->subtotal,
            // 'discount' => (int)$this->discount,
            // 'net' => $this->net,

            // 'sku' => $this->penjualan->sku,
            // 'total' => $this->penjualan->total,
            // 'disc_total' => $this->penjualan->disc_final,
            // 'net_total' => $this->penjualan->net_total,
            // 'total_qty' => $this->penjualan->total_qty,
            // 'total_pcs' => $this->penjualan->total_pcs,
            // 'ppn' => $this->penjualan->ppn,
            // 'grand_total' => $this->penjualan->grand_total,

            // 'created_by' => $this->created_by,
            // 'updated_by' => $this->updated_by,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at
        ];
    }
}

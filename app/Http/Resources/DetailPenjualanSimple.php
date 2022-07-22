<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DetailPenjualanSimple extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // if($this->harga_barang->id == 0){
        //     $nama_barang = '[EXTRA] ' . $this->nama_barang;
        // }
        // else{
        //     $nama_barang = $this->nama_barang;
        // }
        $promo = '';
        if ($this->promo != null) {
            $promo = $this->promo->nama_promo;
        }
        return [
            'kode_barang'   => $this->stock->barang->kode_barang,
            'nama_barang'   => strtoupper($this->stock->barang->nama_barang),
            'qty'           => $this->qty,
            'qty_pcs'       => $this->qty_pcs,
            'tipe_harga'    => $this->harga_barang->tipe_harga,
            'harga'         => round(($this->harga_barang->harga / 1.1)), // price_before_tax =  price_after_tax / 1.1;
            'harga_tax'     => round($this->harga_barang->harga),
            'discount'      => round($this->discount),
            'discount_tax'  => round($this->discount_after_tax),
            'subtotal'      => round($this->subtotal),
            'subtotal_tax'  => round($this->subtotal_after_tax),
            'promo'         => $promo,
        ];
    }
}

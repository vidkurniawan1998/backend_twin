<?php


namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class DetailFakturPembelian extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                    => $this->id,
            'id_faktur_pembelian'   => $this->id_faktur_pembelian,
            'id_barang'             => $this->id_barang,
            'kode_barang'           => $this->barang->kode_barang,
            'nama_barang'           => $this->barang->nama_barang,
            'qty'                   => $this->qty,
            'pcs'                   => $this->pcs,
            'harga_barang'          => $this->harga,
            'disc_persen'           => $this->disc_persen,
            'disc_value'            => $this->disc_value,
            'subtotal'              => $this->subtotal
        ];
    }
}
<?php


namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class DetailPenerimaanBarangSimple extends JsonResource
{
    public function toArray($request)
    {
        return [
            'kode_barang'   => $this->barang->kode_barang,
            'nama_barang'   => $this->barang->nama_barang,
            'qty'           => $this->qty,
            'pcs'           => $this->pcs,
            'harga'         => $this->price_before_tax,
            'ppn'           => 0,
            'disc_persen'   => 0,
            'disc_rupiah'   => 0,
            'subtotal'      => $this->sub_total
        ];
    }
}

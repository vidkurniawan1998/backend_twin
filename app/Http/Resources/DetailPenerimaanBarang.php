<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DetailPenerimaanBarang extends JsonResource
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
            'id_penerimaan_barang' => $this->id_penerimaan_barang,
            'id_barang' => $this->id_barang,
            'id_harga' => $this->id_harga,
            'qty' => (int)$this->qty,
            'qty_pcs' => (int)$this->qty_pcs,
            'keterangan' => $this->keterangan,

            'kode_barang' => $this->kode_barang,
            'nama_barang' => strtoupper($this->nama_barang),
            'satuan' => $this->barang->satuan,

            'harga' => round($this->price_after_tax,2),
            'price_before_tax' => round($this->price_before_tax,2),
            // 'price' => 0, // round($price,2)  double(11,2)
            // 'disc_p' => 0, // double(5,2)
            // 'disc_p_nom' => 0, // double(11,2)
            // 'disc_n' => 0, // double(11,2)
            // 'total_disc' => 0, // double(11,2)
            'subtotal' => round($this->subtotal,2), // double(11,2)
            'ppn' => round($this->ppn,2), // double(11,2)
            'grand_total' => round($this->grand_total,2), // double(11,2)


            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            // 'deleted_by' => $this->deleted_by,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
            // 'deleted_at' => (string) $this->deleted_at
        ];
    }
}

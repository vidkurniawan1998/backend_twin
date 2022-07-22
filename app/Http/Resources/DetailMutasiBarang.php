<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DetailMutasiBarang extends JsonResource
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
            'id_mutasi_barang' => $this->id_mutasi_barang,
            'id_stock' => $this->id_stock,
            'qty' => $this->qty,
            'qty_pcs' => $this->qty_pcs,
            'keterangan' => $this->keterangan,

            'id_barang' => $this->stock->id_barang,
            'kode_barang' => $this->kode_barang,
            'nama_barang' => $this->nama_barang,
            'satuan' => $this->stock->barang->satuan,

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            // 'deleted_by' => $this->deleted_by,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
            // 'deleted_at' => (string) $this->deleted_at
        ];
    }
}

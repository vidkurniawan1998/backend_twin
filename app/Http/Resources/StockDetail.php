<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class StockDetail extends Resource
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
            'id_gudang' => $this->id_gudang,
            'id_barang' => $this->id_barang,
            'qty' => (int)$this->qty,
            'qty_pcs' => (int)$this->qty_pcs,

            'qty_available' => $this->qty_available,
            'qty_waiting' => (int)$this->qty - $this->qty_available,
            
            'nama_gudang' => $this->gudang->nama_gudang,
            // 'kode_gudang' => $this->gudang->kode_gudang,
            'jenis' => $this->gudang->jenis,

            'kode_barang' => $this->kode_barang,
            'nama_barang' => $this->nama_barang,
            'barcode' => $this->barang->barcode,
            'gambar' => $this->barang->gambar,
            'satuan' => $this->barang->satuan,
            'isi' => $this->isi,
            'berat' => floatval($this->barang->berat),
            'dbp' => floatval($this->dbp),

            // 'created_by' => $this->created_by,
            // 'updated_by' => $this->updated_by,
            // 'deleted_by' => $this->deleted_by,
            // 'created_at' => (string) $this->created_at,
            // 'updated_at' => (string) $this->updated_at,
            // 'deleted_at' => (string) $this->deleted_at,
        ];
    }
}
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BarangByBrand extends JsonResource
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
            'kode_barang' => $this->kode_barang,
            'barcode' => $this->barcode,
            'nama_barang' => $this->nama_barang,
            'berat' => $this->berat,
            'isi' => $this->isi,
            'satuan' => $this->satuan,
            'nama_segmen' => $this->segmen->nama_segmen,
            'nama_brand'  => $this->segmen->brand->nama_brand
        ];
    }
}

?>
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DetailPelunasanPenjualan extends JsonResource
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
            'tanggal' => $this->tanggal,
            'no_invoice' => $this->penjualan->no_invoice,
            'no_acc' => $this->penjualan->toko->no_acc,
            'cust_no' => $this->penjualan->toko->cust_no,
            'id_toko' => $this->penjualan->id_toko,
            'nama_toko' => $this->penjualan->toko->nama_toko,
            'tipe' => $this->tipe,
            'nominal' => $this->nominal,
            'status' => $this->status,
            'bank' => $this->bank,
            'no_rekening' => $this->no_rekening,
            'no_bg' => $this->no_bg,
            'jatuh_tempo_bg' => $this->jatuh_tempo_bg,
            'keterangan' => $this->keterangan,
            'nama_salesman' => $this->penjualan->salesman->user->name,
            'nama_tim' => $this->penjualan->nama_tim,

            'approved_by' => $this->approved_by,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'approved_at' => (string) $this->approved_at,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at
        ];
    }
}

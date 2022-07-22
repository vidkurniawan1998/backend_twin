<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DetailPelunasanPenjualanReport extends JsonResource
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
            'bank' => $this->bank,
            'cust_no' => $this->cust_no,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
            'deleted_at' => $this->deleted_at,
            'deleted_by' => $this->deleted_by,
            'delivered_at' => $this->delivered_at,
            'id' => $this->id,
            'id_depo' => $this->id_depo,
            'id_penjualan' => $this->id_penjualan,
            'id_salesman' => $this->id_salesman,
            'id_toko' => $this->id_toko,
            'jatuh_tempo_bg' => $this->jatuh_tempo_bg,
            'keterangan' => $this->keterangan,
            'nama_toko' => $this->nama_toko,
            'no_acc' => $this->no_acc,
            'no_bg' => $this->no_bg,
            'no_invoice' => $this->no_invoice,
            'no_rekening' => $this->no_rekening,
            'nominal' => $this->nominal,
            'status' => $this->status,
            'tanggal' => $this->tanggal,
            'tipe' => $this->tipe,
            'updated_at' => $this->updated_at,
            'updated_by' => $this->updated_by,

            'nama_tim' => $this->penjualan->salesman->tim->nama_tim,
            'penjualan' => $this->penjualan,  
        ];
    }
}

<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class DetailReturPenjualan extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $approved_at = $this->whenLoaded('retur_penjualan')->approved_at ?? '';

        return [
            'id'                    => $this->id,
            'id_retur_penjualan'    => $this->id_retur_penjualan,
            'id_barang'             => $this->id_barang,
            'tipe_barang'           => $this->barang->tipe,
            'kategori_bs'           => $this->kategori_bs,
            'expired_date'          => $this->expired_date,
            'qty_dus_order'         => $this->qty_dus_order,
            'qty_pcs_order'         => $this->qty_pcs_order,
            'qty_dus'               => $this->qty_dus,
            'qty_pcs'               => $this->qty_pcs,
            'disc_persen'           => $this->disc_persen,
            'disc_nominal'          => $this->disc_nominal,
            'harga'                 => round($this->harga),
            'harga_ppn'             => round($this->harga * 1.1),
            'value_retur_percentage'=> $this->value_retur_percentage,
            'subtotal'              => round($this->subtotal),
            'subtotal_ppn'          => round($this->subtotal_after_tax),
            'discount'              => round($this->discount),
            'discount_ppn'          => round($this->discount_after_tax),
            'dpp'                   => round($this->dpp),
            'net'                   => round($this->net),
            'kode_barang'           => $this->whenLoaded('barang')->kode_barang ?? '',
            'nama_barang'           => $this->whenLoaded('barang')->nama_barang ?? '',
            'dbp'                   => round($this->dbp,2),
            'periode'               => $this->periode
        ];
    }
}

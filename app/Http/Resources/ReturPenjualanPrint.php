<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReturPenjualanPrint extends JsonResource
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
            'id'                => $this->id,
            'tanggal'           => $this->sales_retur_date,
            'no_invoice'        => $this->no_invoice,
            'no_retur_manual'   => $this->no_retur_manual,
            'id_toko'           => $this->id_toko,
            'nama_toko'         => $this->toko->nama_toko ?? '',
            'no_acc_toko'       => $this->toko->no_acc,
            'no_cust_toko'      => $this->toko->cust_no,
            'alamat_toko'       => $this->toko->alamat,
            'npwp'              => $this->toko->ketentuan_toko->npwp ?? '',
            'nama_pkp'          => $this->toko->ketentuan_toko->nama_pkp ?? '',
            'alamat_pkp'        => $this->toko->ketentuan_toko->alamat_pkp ?? '',
            'id_perusahaan'     => $this->depo->id_perusahaan,
            'nama_perusahaan'   => $mitra->perusahaan ?? $this->depo->perusahaan->nama_perusahaan,
            'kode_perusahaan'   => $this->depo->perusahaan->kode_perusahaan,
            'npwp_perusahaan'   => $this->depo->perusahaan->npwp,
            'nama_pkp_perusahaan'     => $this->depo->perusahaan->nama_pkp,
            'alamat_pkp_perusahaan'   => $this->depo->perusahaan->alamat_pkp,
            'faktur_pajak_pembelian'         => $this->faktur_pajak_pembelian,
            'tanggal_faktur_pajak_pembelian' => $this->tanggal_faktur_pajak_pembelian,
            'subtotal'          => round($this->subtotal),
            'subtotal_ppn'      => round($this->subtotal_after_tax),
            'discount'          => round($this->discount),
            'discount_ppn'      => round($this->discount_after_tax),
            'dpp'               => round($this->dpp),
            'ppn'               => round($this->ppn),
            'grand_total'       => round($this->grand_total),
            'total_dus'         => $this->total_dus,
            'total_pcs'         => $this->total_pcs,
            'detail_retur_penjualan'         => DetailReturPenjualan::collection($this->whenLoaded('detail_retur_penjualan'))
        ];
    }
}

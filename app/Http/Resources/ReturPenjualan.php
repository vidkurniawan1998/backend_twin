<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReturPenjualan extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $mitra = $this->mitra ?? null;
        return [
            'id'                => $this->id,
            'no_invoice'        => $this->no_invoice,
            'no_retur_manual'   => $this->no_retur_manual,
            'id_salesman'       => $this->id_salesman,
            'nama_salesman'     => $this->salesman->user->name ?? '',
            'nama_tim'          => $this->tim->nama_tim ?? '',
            'tipe_tim'          => $this->tim->tipe ?? '',
            'id_toko'           => $this->id_toko,
            'nama_toko'         => $this->toko->nama_toko ?? '',
            'no_acc_toko'       => $this->toko->no_acc,
            'no_cust_toko'      => $this->toko->cust_no,
            'alamat_toko'       => $this->toko->alamat,
            'npwp'              => $this->npwp ?? '',
            'id_gudang'         => $this->id_gudang,
            'nama_gudang'       => $this->whenLoaded('gudang')->nama_gudang,
            'tipe_retur'        => $this->tipe_retur,
            'tipe_barang'       => $this->tipe_barang,
            'sales_retur_date'  => $this->sales_retur_date,
            'claim_date'        => $this->claim_date,
            'keterangan'        => $this->keterangan,
            'status'            => $this->status,
            'subtotal'          => round($this->subtotal),
            'subtotal_ppn'      => round($this->subtotal_after_tax),
            'discount'          => round($this->discount),
            'discount_ppn'      => round($this->discount_after_tax),
            'dpp'               => round($this->dpp),
            'ppn'               => round($this->ppn),
            'grand_total'       => round($this->grand_total),
            'pic'               => optional($this->pic)->name,
            'verified_by'       => $this->verified_by,
            'nama_koordinator'  => $this->whenLoaded('user_verify', function () {
                return $this->user_verify->name;
            }),
            'created_by'        => $this->created_by,
            'updated_by'        => $this->updated_by,
            'approved_by'       => $this->approved_by,
            'created_at'        => (string) $this->created_at,
            'updated_at'        => (string) $this->updated_at,
            'approved_at'       => (string) $this->approved_at,
            'verified_at'       => (string) $this->verified_at,
            'alamat_depo'       => $mitra->alamat ?? $this->depo->alamat,
            'nama_depo'         => $this->depo->nama_depo,
            'telp_depo'         => $mitra->telp ?? $this->depo->telp,
            'kabupaten_depo'    => $mitra->kabupaten ?? $this->depo->kabupaten,
            'fax_depo'          => $mitra->fax ?? $this->depo->fax,
            'id_perusahaan'     => $this->depo->id_perusahaan,
            'id_depo'           => $this->depo->id,
            'nama_perusahaan'   => $mitra->perusahaan ?? $this->depo->perusahaan->nama_perusahaan,
            'kode_perusahaan'   => $this->depo->perusahaan->kode_perusahaan,
            'total_dus'         => $this->total_dus,
            'total_pcs'         => $this->total_pcs,
            'id_mitra'          => $this->id_mitra,
            'potongan'          => $this->potongan,
            'faktur_pajak_pembelian' => $this->faktur_pajak_pembelian,
            'tanggal_faktur_pajak_pembelian' => $this->tanggal_faktur_pajak_pembelian
        ];
    }
}

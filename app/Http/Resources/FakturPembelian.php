<?php


namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class FakturPembelian extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'no_invoice'        => $this->no_invoice,
            'faktur_pajak'      => $this->faktur_pajak,
            'tanggal_invoice'   => $this->tanggal_invoice,
            'tanggal_jatuh_tempo' => $this->tanggal_jatuh_tempo,
            'tanggal_bayar'     => $this->tanggal_bayar,
            'disc_persen'       => $this->disc_persen,
            'disc_value'        => $this->disc_value,
            'id_perusahaan'     => $this->id_perusahaan,
            'id_depo'           => $this->id_depo,
            'status'            => $this->status,
            'kode_perusahaan'   => $this->whenLoaded('perusahaan', function () {
                return $this->perusahaan->kode_perusahaan;
            }),
            'ppn'               => $this->ppn,
            'id_principal'      => $this->id_principal,
            'nama_principal'    => $this->whenLoaded('principal', function () {
               return $this->principal->nama_principal;
            }),
            'ppn_value'         => $this->ppn_value,
            'subtotal'          => $this->subtotal,
            'discount'          => $this->discount,
            'dpp'               => $this->dpp,
            'grand_total'       => $this->grand_total,
            'over_due'          => $this->over_due,
            'penerimaan_barang' => $this->penerimaan_barang,
            'detail_faktur_pembelian' => DetailFakturPembelian::collection($this->whenLoaded('detail_faktur_pembelian'))
        ];
    }
}
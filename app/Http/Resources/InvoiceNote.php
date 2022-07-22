<?php


namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceNote extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'no_invoice'        => $this->no_invoice,
            'tanggal_req'       => $this->tanggal,
            'nama_toko'         => $this->penjualan->toko->nama_toko,
            'cust_no'           => $this->penjualan->toko->cust_no,
            'telepon'           => $this->penjualan->toko->telepon,
            'alamat'            => $this->penjualan->toko->alamat,
            'keterangan'        => $this->keterangan,
            'keterangan_reschedule'=> $this->keterangan_reschedule,
            'status'            => $this->status,
            'riwayat'           => $this->riwayat_invoice_note
        ];
    }
}
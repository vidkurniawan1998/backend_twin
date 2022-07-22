<?php


namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceNoteEdit extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'id_penjualan'      => $this->id_penjualan,
            'no_invoice'        => $this->no_invoice,
            'tanggal'           => $this->tanggal,
            'id_depo'           => $this->penjualan->id_depo,
            'id_salesman'       => $this->penjualan->id_salesman,
            'keterangan'        => $this->keterangan,
            'keterangan_reschedule'=> $this->keterangan_reschedule,
            'status'            => $this->status,
        ];
    }
}
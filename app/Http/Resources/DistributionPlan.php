<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DistributionPlan extends JsonResource
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
            'po_manual' => $this->po_manual,
            'no_invoice' => $this->no_invoice,
            'id_toko' => $this->id_toko,
            'nama_toko' => $this->nama_toko,
            'alamat' => $this->alamat,
            'salesman' => $this->salesman,
            'toko' => $this->toko,
            'nama_salesman' => $this->nama_salesman,
            'nama_tim' => $this->nama_tim,
            'tanggal' => $this->tanggal,
            'delivered_at' => (string) $this->delivered_at,
            'tipe_pembayaran' => $this->tipe_pembayaran,
            'tanggal_jadwal' => $this->tanggal_jadwal,
            'driver_id' => $this->driver_id,
            'nama_driver' => $this->nama_driver,
            'nama_checker' => $this->nama_checker,
            'jam_sampai' => $this->delivered_at != null ? $this->delivered_at->format('H:i:s') : '-',
            'gudang' => $this->gudang,
            'week' => $this->week,
            'no_acc' => $this->no_acc,
            'status_verifikasi' => $this->status_verifikasi,
            'total' => round($this->total,2),
            'total_after_tax' => round($this->total_after_tax,2),
            'disc_total' => round($this->disc_final,2),
            'net_total' => round($this->net_total,2),
            'total_qty' => round($this->total_qty,2),
            'total_pcs' => round($this->total_pcs,2),
            'ppn' => round($this->ppn,2),
            'sum_qty' => $this->sum_carton,
            'grand_total' => $this->grand_total,
            'nama_kelurahan' => $this->nama_kelurahan
        ];
    }
}

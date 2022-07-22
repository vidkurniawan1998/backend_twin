<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Toko as TokoResource;
use App\Http\Resources\Salesman as SalesmanResource;
use App\Models\Toko;
use App\Models\Salesman;

class Penjualan extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // $toko = Toko::find($this->id_toko);
        // $salesman = Salesman::find($this->id_salesman);

        return [
            'id' => $this->id,
            'po_manual' => $this->po_manual,
            'no_invoice' => $this->no_invoice,
            'id_toko' => $this->id_toko,
            'no_pajak' => $this->no_pajak,
            'toko' => [
                new TokoResource($this->toko),
            ],

            'id_salesman' => $this->id_salesman,
            'salesman' => [
                new SalesmanResource($this->salesman),
            ],
            'nama_tim' => $this->tim->nama_tim,
            'gudang' => $this->gudang,
            'tanggal' => $this->tanggal,
            'week' => $this->week,
            'tipe_pembayaran' => $this->tipe_pembayaran,
            'tipe_harga' => $this->tipe_harga,
            'keterangan' => $this->keterangan,
            'status' => $this->status,
            'pending_status' => $this->pending_status,
            'paid_at' => $this->paid_at,
            'over_due' => $this->over_due,
            'id_retur' => $this->id_retur,
            'id_pengiriman' => $this->id_pengiriman,

            'latitude' => $this->latitude,
            'longitude' => $this->longitude,

            'sku' => $this->sku,
            'total' => round($this->total,2),
            'total_after_tax' => round($this->total_after_tax,2),
            'disc_total' => round($this->disc_final,2),
            'net_total' => round($this->net_total,2),
            'total_qty' => round($this->total_qty,2),
            'total_pcs' => round($this->total_pcs,2),
            'ppn' => round($this->ppn,2),
            'grand_total' => round($this->grand_total,2),

            'approved_at' => (string) $this->approved_at,
            'approved_by' => $this->approved_by,
            'delivered_at' => (string) $this->delivered_at,
            'delivered_by' => $this->delivered_by,
            'due_date' => $this->due_date,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            // 'deleted_by' => $this->deleted_by,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
            // 'deleted_at' => (string) $this->deleted_at,

            'tanggal_jadwal' => $this->tanggal_jadwal,
            'driver_id' => $this->driver_id,
            'nama_driver' => $this->nama_driver,
            'nama_checker' => $this->nama_checker,
            'jam_sampai' => $this->delivered_at != null ? $this->delivered_at->format('H:i:s') : '-',
            'kode_perusahaan' => $this->perusahaan->kode_perusahaan,
            'print_count' => $this->print_count,
            'id_mitra' => $this->id_mitra,
            'id_tim' => $this->id_tim,
        ];
    }
}

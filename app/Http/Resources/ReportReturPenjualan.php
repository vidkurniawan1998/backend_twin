<?php


namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportReturPenjualan extends JsonResource
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
            'no_invoice'            => $this->retur_penjualan->no_invoice,
            'id_barang'             => $this->id_barang,
            'kategori_retur'        => $this->retur_penjualan->tipe_barang === 'bs' ? 'bad stock':'retur baik',
            'kategori_bs'           => $this->kategori_bs,
            'claim_date'            => $this->retur_penjualan->claim_date,
            'expired_date'          => $this->expired_date,
            'qty_dus_order'         => $this->qty_dus_order,
            'qty_pcs_order'         => $this->qty_pcs_order,
            'qty_dus'               => $this->qty_dus,
            'qty_pcs'               => $this->qty_pcs,
            'harga'                 => round($this->harga),
            'harga_tax'             => round($this->harga * 1.1),
            'value_retur_percentage'=> $this->value_retur_percentage,
            'subtotal'              => round($this->subtotal),
            'sales_retur_date'      => $this->retur_penjualan->sales_retur_date,
            'nama_gudang'           => $this->nama_gudang,
            'no_acc'                => $this->no_acc,
            'cust_no'               => $this->cust_no,
            'nama_toko'             => $this->nama_toko,
            'alamat'                => $this->alamat,
            'tim'                   => $this->retur_penjualan->tim->nama_tim,
            'barang'                => $this->whenLoaded('barang'),
            'dbp'                   => round($this->harga_dbp,2),
            'npwp'                  => $this->npwp ?? '',
            'ppn'                   => round($this->ppn),
            'periode'               => $this->periode,
            'created_by'            => $this->created_by,
            'updated_by'            => $this->updated_by,
            'approved_by'           => $this->retur_penjualan->approved_by,
            'created_at'            => (string) $this->created_at,
            'updated_at'            => (string) $this->updated_at,
            'approved_at'           => $approved_at ? Carbon::parse($approved_at)->toDateString() : '',
            'potongan'              => round($this->discount),
            'net'                   => round($this->net),
            'dpp'                   => round($this->dpp),
            'npr' => $this->retur_penjualan->faktur_pajak
        ];
    }
}

<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesToTrade extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $nama_gudang    = $this->penjualan->gudang ? $this->penjualan->gudang->nama_gudang : $this->stock->gudang->nama_gudang;
        $delivered_at   = $this->penjualan->delivered_at ? Carbon::parse($this->penjualan->delivered_at)->toDateString() : $this->penjualan->tanggal;
        return [
            'nama_gudang' => $nama_gudang,
            'nama_depo' => $this->penjualan->salesman->tim->depo->nama_depo,
            'no_acc' => $this->penjualan->toko->no_acc,
            'nama_toko' => $this->penjualan->toko->nama_toko,
            'alamat_toko' => $this->penjualan->toko->alamat,
            'kabupaten' => $this->penjualan->toko->kabupaten,
            'kecamatan' => $this->penjualan->toko->kecamatan,
            'kelurahan' => $this->penjualan->toko->nama_kelurahan,
            'kode_pos' => $this->penjualan->toko->kode_pos,
            'npwp' => $this->npwp,
            'nama_pkp' => $this->nama_pkp,
            'alamat_pkp' => $this->alamat_pkp,
            'tipe_harga' => $this->harga_barang->tipe_harga,
            'tipe_toko' => $this->penjualan->toko->tipe,
            'kode_barang' => $this->kode_barang,
            'item_code' => $this->stock->barang->item_code,
            'nama_barang' => $this->nama_barang,
            'nama_brand' => $this->nama_brand,
            'nama_segmen' => $this->nama_segmen,
            'isi' => (string)$this->stock->barang->isi,
            'berat' => (string)$this->stock->barang->berat,
            'satuan' => $this->stock->barang->satuan,
            'nama_salesman' => $this->penjualan->salesman->user->name,
            'nama_tim' => $this->penjualan->salesman->tim->nama_tim,
            'qty_dus' => floatval($this->qty),
            'qty_pcs' => floatval($this->qty_pcs),
            'order_dus' => floatval($this->order_qty),
            'order_pcs' => floatval($this->order_pcs),
            'price_after_tax' => floatval($this->harga_barang->harga),
            'price_before_tax' => round(($this->harga_barang->harga / 1.1),2),
            'tahun' => substr($delivered_at, 0, 4),
            'bulan' => substr($delivered_at, 5, 2),
            'hari' => substr($delivered_at, -2, 2),
            'tanggal_penjualan' => $delivered_at,
            'subtotal' => round($this->subtotal,2),
            'discount' => round($this->discount,2),
            'promo' => optional($this->promo)->nama_promo,
            'dpp' => round($this->dpp,2),
            'ppn' => round($this->ppn,2),
            'total' => round($this->total,2),
            'hpp' => round($this->hpp,2),
            'dbp' => round($this->dbp,2), // hargaNet
            'week' => (string)$this->penjualan->week,
            'no_invoice' => $this->penjualan->no_invoice,
            'no_po' => $this->penjualan->po_manual ? (string)$this->penjualan->po_manual : (string) $this->penjualan->id,
            'no_pajak' => $this->penjualan->no_pajak,
            'status' => $this->penjualan->status,
            'ordered_at' => (string) $this->penjualan->created_at,
            'approved_at' => (string) $this->penjualan->approved_at,
            'delivered_at' => (string) date('Y-m-d', strtotime($this->penjualan->delivered_at)),
            'tipe_pembayaran' => $this->penjualan->tipe_pembayaran
        ];
    }
}
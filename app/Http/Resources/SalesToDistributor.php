<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class SalesToDistributor extends Resource
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
            // 'id' => $this->id,

            // 'id_gudang' => $this->penerimaan_barang->id_gudang,
            'gudang' => optional($this->penerimaan_barang->gudang)->nama_gudang,

            // 'id_principal' => $this->penerimaan_barang->id_principal,
            'supplier' => optional($this->penerimaan_barang->principal)->nama_principal,

            // 'id_penerimaan_barang' => $this->id_penerimaan_barang,
            'no_pb' => $this->penerimaan_barang->no_pb,
            'no_do' => $this->penerimaan_barang->no_do,
            'no_spb' => $this->penerimaan_barang->no_spb,
            'tgl_kirim' => $this->penerimaan_barang->tgl_kirim,
            'tgl_datang' => $this->penerimaan_barang->tgl_datang,
            'tgl_bongkar' => $this->penerimaan_barang->tgl_bongkar,
            'driver' => $this->penerimaan_barang->driver,
            'transporter' => $this->penerimaan_barang->transporter,
            'no_pol_kendaraan' => $this->penerimaan_barang->no_pol_kendaraan,
            // 'catatan' => $this->penerimaan_barang->keterangan,

            // 'id_barang' => $this->id_barang,
            'kode_barang' => optional($this->barang)->kode_barang,
            'nama_barang' => optional($this->barang)->nama_barang,
            'segmen' => optional($this->barang->segmen)->nama_segmen,
            'brand' => optional($this->barang->segmen->brand)->nama_brand,
            'satuan' => optional($this->barang)->satuan,
            'isi' => optional($this->barang)->isi,

            'qty_dus' => (int)$this->qty,
            'qty_pcs' => (int)$this->qty_pcs,

            // 'id_harga' => $this->id_harga,
            'harga' => round($this->price_after_tax,2),
            'price_before_tax' => round($this->price_before_tax,2),
            // 'price' => 0, // round($price,2)  double(11,2)
            // 'disc_p' => 0, // double(5,2)
            // 'disc_p_nom' => 0, // double(11,2)
            // 'disc_n' => 0, // double(11,2)
            // 'total_disc' => 0, // double(11,2)
            'subtotal' => round($this->subtotal,2), // double(11,2)
            'ppn' => round($this->ppn,2), // double(11,2)
            'grand_total' => round($this->grand_total,2), // double(11,2)

            // 'keterangan' => $this->keterangan,

            'week' => $this->penerimaan_barang->week,
            'siklus' => $this->penerimaan_barang->siklus,
            'created_at' => (string) $this->created_at,

        ];
    }
}
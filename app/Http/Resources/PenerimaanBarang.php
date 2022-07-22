<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PenerimaanBarang extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $depo = $this->gudang->depo;
        $no_invoice     = '';
        if(count($this->faktur_pembelian)>0){
            $no_invoice = $this->faktur_pembelian[0]->no_invoice;
        }

        return [
            'id'            => $this->id,
            'no_pb'         => $this->no_pb,
            'no_do'         => $this->no_do,
            'no_spb'        => $this->no_spb,
            'no_invoice'    => $no_invoice,
            'id_principal'  => $this->id_principal,
            'id_gudang'     => $this->id_gudang,
            'tgl_kirim'     => $this->tgl_kirim,
            'tgl_datang'    => $this->tgl_datang,
            'tgl_bongkar'   => $this->tgl_bongkar,
            'driver'        => $this->driver,
            'transporter'   => $this->transporter,
            'no_pol_kendaraan' => $this->no_pol_kendaraan,
            'keterangan'    => $this->keterangan,
            'is_approved'   => $this->is_approved,
            'scan'          => $this->scan,
            'depo'          => $depo,
            'perusahaan'    => $this->principal->perusahaan,
            'nama_gudang'   => $this->gudang->nama_gudang,
            'kode_gudang'   => $this->gudang->kode_gudang,
            'jenis'         => $this->gudang->jenis,
            'nama_principal'=> $this->principal->nama_principal,
            'total_qty'     => (int)$this->total_qty,
            'total_pcs'     => (int)$this->total_pcs,

            'subtotal'          => round($this->subtotal,2),
            'ppn'               => round($this->ppn,2),
            'grand_total'       => round($this->grand_total,2),
            'faktur_pembelian'  => $this->faktur_pembelian,

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            // 'deleted_by' => $this->deleted_by,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
            // 'deleted_at' => (string) $this->deleted_at,

        ];
    }
}

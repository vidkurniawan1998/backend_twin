<?php

namespace App\Http\Resources;

use App\Helpers\Helper;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\DetailPenjualanSimple as DetailPenjualanSimpleResource;
use Auth;
use Carbon\Carbon;

class PenjualanWithDetail extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $details = $this->whenLoaded('detail_penjualan');
        if ($details) {
            $details = $details->sortBy('stock.barang.kode_barang');
        }
        $kordinator     = User::where('id',$this->tim->id_sales_koordinator)->first();
        $nama_gudang    = $this->gudang ? $this->gudang->nama_gudang : $this->nama_gudang;
        $mitra = $this->mitra;

        return [
            'id'                => $this->id,
            'po_manual'         => $this->po_manual,
            'invoice'           => $this->no_invoice,
            'tgl_cetak'         => $this->delivered_at <> null ? strtoupper(Carbon::parse($this->delivered_at)->format('d M y H:i')) : strtoupper(Carbon::now()->format('d M y H:i')),
            'tanggal'           => $this->tanggal,
            'tipe_pembayaran'   => strtoupper($this->tipe_pembayaran),
            'keterangan'        => $this->keterangan,
            'no_acc'            => $this->whenLoaded('toko')->no_acc,
            'nama_toko'         => strtoupper($this->whenLoaded('toko')->nama_toko),
            'alamat_toko'       => strtoupper($this->whenLoaded('toko')->alamat),
            'nama_salesman'     => strtoupper($this->whenLoaded('salesman')->user->name),
            'npwp'              => strtoupper($this->whenLoaded('toko')->ketentuan_toko->npwp ?? ''),
            'gudang'            => strtoupper($nama_gudang),
            'nama_tim'          => $this->whenLoaded('tim')->nama_tim,
            'total_qty'         => $this->whenLoaded('detail_penjualan')->sum('qty'),
            'total_pcs'         => $this->whenLoaded('detail_penjualan')->sum('qty_pcs'),
            'subtotal'          => round($this->total),
            'subtotal_tax'      => round($this->total_after_tax),
            'diskon'            => round($this->disc_total),
            'diskon_tax'        => round($this->disc_total_after_tax),
            'username'          => Auth::user()->name,
            'ppn'               => round($this->ppn),
            'grand_total'       => round($this->grand_total),
            'terbilang'         => Helper::terbilang(round($this->grand_total)),
            'detail_penjualan'  => DetailPenjualanSimpleResource::collection($details),
            'nama_perusahaan'   => $mitra->perusahaan ?? $this->depo->perusahaan->nama_perusahaan,
            'alamat_depo'       => $mitra->alamat ?? $this->depo->alamat,
            'nama_depo'         => $this->depo->nama_depo,
            'telp_depo'         => $mitra->telp ?? $this->depo->telp,
            'kabupaten_depo'    => $mitra->kabupaten ?? $this->depo->kabupaten,
            'fax_depo'          => $mitra->fax ?? $this->depo->fax,
            'minggu'            => $this->toko->minggu,
            'hari'              => $this->toko->hari,
            'id_sales_koordinator' => $this->tim->id_sales_koordinator,
            'nama_koordinator'  => $this->tim->id_sales_koordinator != null ? $kordinator->name : null,
            'no_koordinator'    => $this->tim->id_sales_koordinator != null ? $kordinator->phone : '-',
        ];
    }
}

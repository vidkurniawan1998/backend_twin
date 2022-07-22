<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon as Carbon;

class PelunasanPenjualan extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /*
        Carbon::setlocale('id');
        $nameOfDay = Carbon::parse($this->due_date)->translatedFormat('l');
        */
        return [
            'id' => $this->id,
            'no_invoice' => $this->no_invoice,
            'id_toko' => $this->id_toko,
            'nama_toko' => $this->nama_toko,
            'no_acc' => $this->no_acc,
            'cust_no' => $this->cust_no,
            'alamat' => $this->toko->alamat,
            'tanggal_penjualan' => $this->tanggal,
            'due_date' => $this->due_date,
            'over_due' => $this->over_due,
            'tipe_pembayaran' => $this->tipe_pembayaran,
            'delivered_at' => date('Y-m-d', strtotime($this->delivered_at)),
            'tim' => $this->nama_tim,
            'nama_salesman' => $this->salesman->user->name,
            'jumlah_pembayaran' => round($this->grand_total,0),
            'jumlah_lunas' => round($this->jumlah_lunas,0),
            'jumlah_belum_bayar' => round($this->jumlah_belum_bayar,0),
            'paid_at' => $this->paid_at,
            'keterangan' => $this->keterangan,
            'hari_jatuh_tempo' => $this->toko->ketentuan_toko->hari,
            'minggu' => $this->toko->ketentuan_toko->minggu,
            'id_mitra' => $this->id_mitra,
            'kode_mitra' => $this->id_mitra ? $this->mitra->kode_mitra:''
        ];
    }
}

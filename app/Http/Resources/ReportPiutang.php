<?php


namespace App\Http\Resources;


use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportPiutang extends JsonResource
{
    public function toArray($request)
    {
        $toko = $this->toko;
        $waiting = round($this->jumlah_waiting);
        $pembayaran = round($this->jumlah_lunas);
        return [
            'cust_no'           => $toko->cust_no == '' ? $toko->no_acc : $toko->cust_no,
            'nama_toko'         => $toko->nama_toko,
            'alamat'            => $toko->alamat,
            'tanggal_po'        => $this->tanggal,
            'delivered_at'      => Carbon::parse($this->delivered_at)->format('Y-m-d'),
            'due_date'          => $this->due_date,
            'no_invoice'        => $this->no_invoice,
            'no_po'             => $this->id,
            'grand_total'       => round($this->grand_total),
            'pembayaran'        => $pembayaran - $waiting,
            'piutang'           => round($this->jumlah_belum_bayar),
            'over_due'          => $this->over_due,
            'tim'               => $this->nama_tim,
            'tim_baru'          => $toko->ketentuan_toko->tim->nama_tim,
            'tipe_pembayaran'   => $this->tipe_pembayaran,
            'waiting'           => $waiting
        ];
    }
}

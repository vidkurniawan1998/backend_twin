<?php


namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class PelunasanPembelian extends JsonResource
{
    public function toArray($request)
    {
        $principal    = $this->principal;
        $grand_total  = $this->grand_total;
        $pembayaran   = $this->detail_pelunasan_pembelian->where('status','approved')->sum('nominal');
        $status_lunas = round($grand_total)<=round($pembayaran) ? 'lunas' : 'belum_lunas';
        return [
                'id'                 => $this->id,
                'no_invoice'         => $this->no_invoice,
                'tanggal_invoice'    => $this->tanggal_invoice,
                'tanggal_jatuh_tempo'=> $this->tanggal_jatuh_tempo,
                'tanggal_bayar'      => $this->tanggal_bayar,
                'disc_persen'        => $this->disc_persen,
                'disc_value'         => $this->disc_value,
                'id_perusahaan'      => $this->id_perusahaan,
                'id_depo'            => $this->id_depo,
                'id_faktur_pembelian'=> $this->id,
                'status'             => $this->status,
                'status_lunas'       => $status_lunas,
                'nama_principal'     => $principal['nama_principal'],
                'total_pembelian'    => $grand_total,
                'total_pembayaran'   => $pembayaran
            ];
    }

}

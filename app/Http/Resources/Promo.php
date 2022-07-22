<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Promo extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $nama_barang = null;
        if($this->id_barang) {
            if ($this->barang) {
                $nama_barang = $this->barang->nama_barang;
            }
        }

        return [
            'id'            => $this->id,
            'no_promo'      => $this->no_promo,
            'nama_promo'    => $this->nama_promo,
            'status'        => $this->status,
            'status_klaim'  => $this->status_klaim,
            'keterangan'    => $this->keterangan,
            'disc_persen'   => $this->disc_persen,
            'disc_1'        => $this->disc_1,
            'disc_2'        => $this->disc_2,
            'disc_3'        => $this->disc_3,
            'disc_4'        => $this->disc_4,
            'disc_5'        => $this->disc_5,
            'disc_6'        => $this->disc_6,
            'tanggal_awal'  => $this->tanggal_awal,
            'tanggal_akhir' => $this->tanggal_akhir,
            'disc_rupiah'   => $this->disc_rupiah,
            'id_barang'     => $this->id_barang,
            'volume_extra'  => $this->volume_extra,
            'pcs_extra'     => $this->pcs_extra,
            'nama_barang'   => $nama_barang,
            'id_perusahaan' => $this->whenLoaded('perusahaan', function () {
                return $this->perusahaan->id;
            }),
            'kode_perusahaan' => $this->whenLoaded('perusahaan', function () {
                return $this->perusahaan->kode_perusahaan;
            }),
            'depo'          => $this->whenLoaded('depo'),
            'created_by'    => $this->created_by,
            'updated_by'    => $this->updated_by,
            'created_at'    => (string) $this->created_at,
            'updated_at'    => (string) $this->updated_at,
            'toko'          => $this->whenLoaded('promo_toko', function() {
                return TokoListSimple::collection($this->promo_toko);
            }),
            'barang'        => $this->whenLoaded('promo_barang', function() {
                return PromoBarang::collection($this->promo_barang);
            }),
            'salesman' => $this->salesman,
            'disc_rupiah_distributor'   => $this->disc_rupiah_distributor,
            'disc_rupiah_principal'     => $this->disc_rupiah_principal,
            'id_principal'  => $this->id_principal,
            'minimal_order' => $this->minimal_order
        ];
    }
}

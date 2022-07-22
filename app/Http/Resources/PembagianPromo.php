<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PembagianPromo extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $disc_persen_distributor = 0;
        $disc_persen_principal   = 0;
        $disc_rupiah_distributor_carton = (float)$this->disc_rupiah_distributor;
        $disc_rupiah_principal_carton   = (float) $this->disc_rupiah_principal;
        $qty         = (float)$this->qty;
        $disc_rupiah = (float)$this->disc_rupiah;

        $subtotal         = $this->subtotal;
        for ($i=1; $i <=6 ; $i++) {
            $res =  $subtotal*(float)($this['disc_'.$i])/100;
            $disc_persen_distributor  = $i<=4 ? $disc_persen_distributor+$res : $disc_persen_distributor;
            $disc_persen_principal    = $i>4  ? $disc_persen_principal+$res : $disc_persen_principal;
            $subtotal                -= $res;
        }
        $disc_persen_all = $disc_persen_principal+$disc_persen_distributor;
        $disc_rupiah_all = $disc_rupiah_principal_carton+$disc_rupiah_distributor_carton;

        $persentase_distributor = $disc_persen_all>0 ? $disc_persen_distributor/$disc_persen_all : 0;
        $persentase_principal   = $disc_persen_all>0 ? $disc_persen_principal/$disc_persen_all : 0;

        $disc_rupiah_distributor =  $disc_rupiah_all>0 ? $disc_rupiah*$qty*$disc_rupiah_distributor_carton/$disc_rupiah_all : 0;
        $disc_rupiah_principal   =  $disc_rupiah_all>0 ? $disc_rupiah*$qty*$disc_rupiah_principal_carton/$disc_rupiah_all : 0;


        return [
            'id'          => $this->id,
            'id_promo'    => $this->id_promo,
            'nama_perusahaan' => $this->nama_perusahaan,
            'nama_depo'   => $this->nama_depo,
            'kode_barang' => $this->kode_barang,
            'nama_barang' => $this->nama_barang,
            'no_promo'    => $this->no_promo,
            'nama_promo'  => $this->nama_promo,
            'extra'       => $this->extra,
            'qty'         => $qty,
            'subtotal'    => (float)$this->subtotal,
            'disc_rupiah' => $disc_rupiah,
            'disc_rupiah_distributor_carton' => $disc_rupiah_distributor_carton,
            'disc_rupiah_principal_carton'   => $disc_rupiah_principal_carton,
            'disc_1' => (float)$this->disc_1,
            'disc_2' => (float)$this->disc_2,
            'disc_3' => (float)$this->disc_3,
            'disc_4' => (float)$this->disc_4,
            'disc_5' => (float)$this->disc_5,
            'disc_6' => (float)$this->disc_6,
            'disc_persen_principal'     => $disc_persen_principal,
            'disc_persen_distributor'   => $disc_persen_distributor,
            'disc_rupiah_distributor'   => $disc_rupiah_distributor,
            'disc_rupiah_principal'     => $disc_rupiah_principal,
            'persentase_distributor'    => $persentase_distributor,
            'persentase_principal'      => $persentase_principal
        ];
    }
}

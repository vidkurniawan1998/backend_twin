<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class LaporanPenjualan extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if($this->id_tim != null){
            $tim = $this->tim->nama_tim;
            $id_depo = $this->tim->id_depo;
            $depo = $this->tim->depo->nama_depo;
            $id_gudang = $this->tim->depo->id_gudang;
            $gudang = $this->tim->depo->gudang->nama_gudang;
            $tipe = $this->tim->tipe;
        }
        else{
            $tim = null;
            $id_depo = null;
            $depo = null;
            $id_gudang = null;
            $gudang = null;
            $tipe = null;
        }

        return [
            'id' => $this->user_id,
            'nama_salesman' => $this->user->name,
            'phone' => $this->user->phone,
            'tipe' => $tipe,
            'id_tim' => $this->id_tim,
            'tim' => $tim,
            'id_depo' => $id_depo,
            'depo' => $depo,
            'id_gudang' => $id_gudang,
            'gudang' => $gudang,
        ];
    }
}
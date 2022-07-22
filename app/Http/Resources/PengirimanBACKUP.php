<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;
use App\Http\Resources\Driver as DriverResource;
use App\Models\Driver;
use App\Http\Resources\Kendaraan as KendaraanResource;
use App\Models\Kendaraan;
// use App\Models\Penjualan;
// use App\Http\Resources\Penjualan as PenjualanResource;

class PengirimanBACKUP extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // $list_penjualan = Penjualan::where('id_pengiriman', $this->id)->get();
        // $grand_total = 0;
        // foreach($list_penjualan as $lp){
        //     $grand_total = $grand_total + $lp->grand_total;
        // }

        return [
            'id' => $this->id,
            'id_gudang' => $this->id_gudang,
            'nama_gudang' => $this->nama_gudang,
            'id_driver' => $this->id_driver,
            'id_kendaraan' => $this->id_kendaraan,
            'tgl_pengiriman' => $this->tgl_pengiriman,

            //driver
            'driver' => [
                new DriverResource(Driver::find($this->id_driver)),
            ],

            //kendaraan
            'kendaraan' => [
                new KendaraanResource(Kendaraan::find($this->id_kendaraan)),
            ],

            'status' => $this->status,
            'keterangan' => $this->keterangan,

            // 'grand_total' => $grand_total,
            // 'grand_total' => $list_penjualan,

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            // 'deleted_by' => $this->deleted_by,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
            // 'deleted_at' => (string) $this->deleted_at,
        ];
    }
}

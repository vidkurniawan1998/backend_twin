<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;
use App\Http\Resources\Gudang as GudangResource;
// use App\Http\Resources\Driver as DriverResource;
// use App\Models\Driver;
// use App\Http\Resources\Kendaraan as KendaraanResource;
// use App\Models\Kendaraan;
// use App\Models\Penjualan;
// use App\Http\Resources\Penjualan as PenjualanResource;

class Pengiriman extends Resource
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

            'id' => $this->id,
            'id_gudang' => $this->id_gudang,
            'nama_gudang' => $this->whenLoaded('gudang')->nama_gudang,
            'id_driver' => $this->id_driver,
            'nama_driver' => $this->whenLoaded('driver')->user->name,
            'id_kendaraan' => $this->id_kendaraan,
            'no_pol_kendaraan' => $this->whenLoaded('kendaraan')->no_pol_kendaraan,
            'body_no' => $this->whenLoaded('kendaraan')->body_no,
            'tgl_pengiriman' => $this->tgl_pengiriman,

            // //driver
            // 'driver' => [
            //     new DriverResource(Driver::find($this->id_driver)),
            // ],
            // //kendaraan
            // 'kendaraan' => [
            //     new KendaraanResource(Kendaraan::find($this->id_kendaraan)),
            // ],

            'status' => $this->status,
            'keterangan' => $this->keterangan,
            'penjualan_count' => $this->penjualan_count,

            // 'created_by' => $this->created_by,
            // 'updated_by' => $this->updated_by,
            // 'deleted_by' => $this->deleted_by,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
            // 'deleted_at' => (string) $this->deleted_at,
        ];
    }
}

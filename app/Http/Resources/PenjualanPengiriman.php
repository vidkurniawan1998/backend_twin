<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;
// use App\Http\Resources\Toko as TokoResource;
// use App\Http\Resources\Salesman as SalesmanResource;
// use App\Models\Toko;
// use App\Models\Salesman;

class PenjualanPengiriman extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // $toko = Toko::find($this->id_toko);
        // $salesman = Salesman::find($this->id_salesman);

        return [
            'id' => $this->id,
            // 'toko' => [
            //     new TokoResource($toko),
            // ],
            'id_toko' => $this->id_toko,
            'no_acc' => $this->whenLoaded('toko')->no_acc,
            'nama_toko' => $this->whenLoaded('toko')->nama_toko,
            'alamat_toko' => $this->whenLoaded('toko')->alamat,
            // 'kelurahan' => $kelurahan,
            // 'kecamatan' => $kecamatan,
            // 'kabupaten' => $kabupaten,
           
            // 'salesman' => [
            //     new SalesmanResource($salesman),
            // ],
            'id_salesman' => $this->id_salesman,
            'nama_salesman' => $this->whenLoaded('salesman')->user->name,
            'tim_salesman' => $this->whenLoaded('salesman')->tim->nama_tim,
            // 'id_gudang' => $this->salesman->tim->depo->id_gudang,
            // 'gudang_salesman' => $this->salesman->tim->depo->gudang->nama_gudang,

            'tanggal' => $this->tanggal,
            // 'week' => $this->week,
            'tipe_pembayaran' => $this->tipe_pembayaran,
            'keterangan' => $this->keterangan,
            'status' => $this->status,
            // 'paid_at' => $this->paid_at,
            // 'over_due' => $this->over_due,
            // 'id_retur' => $this->id_retur,
            'id_pengiriman' => $this->id_pengiriman,
            'delivered_at' => $this->delivered_at,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            
            // 'sku' => $this->sku,
            // 'disc_total' => $this->disc_final,
            // 'net_total' => $this->net_total,
            // 'ppn' => $this->ppn,
            'total_qty' => $this->total_qty,
            'total_pcs' => $this->total_pcs,
            'grand_total' => $this->grand_total,

            // 'created_by' => $this->created_by,
            // 'updated_by' => $this->updated_by,
            // 'deleted_by' => $this->deleted_by,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
            // 'deleted_at' => (string) $this->deleted_at,
        ];
    }
}

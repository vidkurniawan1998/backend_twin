<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Toko extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // if ($this->ketentuan_toko->id_toko) {
        //     $id_tim = $this->id_tim;
        //     $nama_tim = $this->ketentuan_toko->tim->nama_tim;
        //     $nama_depo = $this->ketentuan_toko->tim->depo->nama_depo;
        //     $k_t = $this->ketentuan_toko->k_t;
        //     // $top = $this->ketentuan_toko->top;
        //     $limit = $this->ketentuan_toko->limit;
        //     $minggu = $this->ketentuan_toko->minggu;
        //     $hari = $this->ketentuan_toko->hari;
        // }
        // else {
        //     $id_tim = null;
        //     $nama_tim = null;
        //     $nama_depo = null;
        //     $k_t = null;
        //     // $top = null;
        //     $limit = null;
        //     $minggu = null;
        //     $hari = null;
        // }

        if($this->id_kelurahan){
            $kelurahan = ucwords(strtolower($this->kelurahan->nama_kelurahan));
        }
        else{
            $kelurahan = null;
        }

        return [
            'id' => $this->id,
            'nama_toko' => $this->nama_toko,
            'tipe' => $this->tipe,
            'tipe_2' => $this->tipe_2,
            'tipe_3' => $this->tipe_3,
            'tipe_harga' => $this->tipe_harga,
            'pemilik' => $this->pemilik,
            'no_acc' => $this->no_acc,
            'cust_no' => $this->cust_no,
            'kode_mars' => $this->kode_mars,
            'telepon' => $this->telepon,
            'alamat' => $this->alamat,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'kode_pos' => $this->kode_pos,
            'id_kabupaten' => substr($this->id_kelurahan, 0, 4),
            'id_kecamatan' => substr($this->id_kelurahan, 0, 7),
            'id_kelurahan' => $this->id_kelurahan,
            'kabupaten' => $this->kabupaten,
            'kecamatan' => $this->kecamatan,
            'kelurahan' => $kelurahan,
            'id_tim' => $this->id_tim,
            'nama_tim' => $this->nama_tim,
            'id_principal' => $this->id_principal,
            'kode_eksklusif' => $this->kode_eksklusif,
            'nama_depo' => $this->whenLoaded('depo', function () {
                return $this->depo->nama_depo;
            }),
            'kode_perusahaan' => $this->whenLoaded('depo', function () {
                return $this->depo->perusahaan->kode_perusahaan;
            }),
            'id_perusahaan' => $this->whenLoaded('depo', function () {
                return $this->depo->perusahaan->id;
            }),
            'nama_principal' => $this->whenLoaded('principal', function () {
               return $this->principal->nama_principal ?? '';
            }),
            'id_depo' => $this->id_depo,
            'k_t' => $this->k_t,
            'limit' => $this->limit,
            'top' => $this->top,
            'npwp' => $this->npwp,
            'nama_pkp' => $this->nama_pkp,
            'alamat_pkp' => $this->ketentuan_toko->alamat_pkp,
            'no_ktp' => $this->ketentuan_toko->no_ktp,
            'nama_ktp' => $this->ketentuan_toko->nama_ktp,
            'alamat_ktp' => $this->ketentuan_toko->alamat_ktp,
            'minggu' => $this->minggu,
            'hari' => $this->hari,
            'saldo_retur' => $this->ketentuan_toko->saldo_retur,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
            'status_verifikasi' => $this->status_verifikasi,
            'id_mitra' => $this->id_mitra,
            'lock_order' => $this->lock_order
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\TipeHarga;
use App\Models\HargaBarang;
use DB;

class Barang extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $perusahaan = $this->id_perusahaan;
        $tipe_harga = TipeHarga::with('perusahaan')
            ->whereHas('perusahaan', function ($q) use ($perusahaan) {
                $q->where('id_perusahaan', $perusahaan);
            })->get();
        $list_harga = [];
        $default_ppn = 0;
        $n = 0;
        foreach ($tipe_harga as $key => $harga) {
            $tipe = strtolower($harga->tipe_harga);
            $harga_barang = HargaBarang::select('harga','harga_non_ppn','ppn','ppn_value')->where('id_barang', $this->id)
                ->where('tipe_harga', $tipe)->latest()->first() ?? ['harga' => 0, 'harga_non_ppn' => 0, 'ppn' => 0, 'ppn_value' => 0];
            $list_harga[$tipe] = $harga_barang;
            $n = $harga_barang['ppn']>0 ? $n+1 : $n ;
            $default_ppn += $harga_barang['ppn'];
        }
        $default_ppn = $n > 0 ? $default_ppn / $n : 0;
        $nama_depo = '';
        foreach ($this->depo as $key => $row) {
            $nama_depo = $key == 0 ? $row->nama_depo : $nama_depo . ', ' . $row->nama_depo;
        }

        $data = [
            'id'            => $this->id,
            'kode_barang'   => $this->kode_barang,
            'item_code'     => $this->item_code,
            'pcs_code'      => $this->pcs_code,
            'barcode'       => $this->barcode,
            'nama_barang'   => $this->nama_barang,
            'nama_segmen'   => $this->segmen->nama_segmen,
            'nama_brand'    => $this->segmen->brand->nama_brand,
            'nama_pricipal' => $this->segmen->brand->principal->nama_principal,
            'berat'         => $this->berat,
            'isi'           => $this->isi,
            'satuan'        => $this->satuan,
            'kelipatan_order' => $this->kelipatan_order,
            'id_segmen'     => $this->id_segmen,
            'deskripsi'     => $this->deskripsi,
            'gambar'        => $this->gambar,
            'default_ppn'   => $default_ppn,
            'extra'         => $this->extra,
            'status'        => $this->status,
            'tipe'          => $this->tipe,
            'created_by'    => $this->created_by,
            'updated_by'    => $this->updated_by,
            'created_at'    => (string) $this->created_at,
            'updated_at'    => (string) $this->updated_at,
            'nama_depo'     => $nama_depo,
            'nama_perusahaan' => $this->perusahaan->nama_perusahaan,
            'kode_perusahaan' => $this->perusahaan->kode_perusahaan,
            'depo'          => $this->whenLoaded('depo'),
            'id_perusahaan' => $this->id_perusahaan,
            'perusahaan'    => new Perusahaan($this->whenLoaded('perusahaan'))
        ];

        $data = array_merge($data, $list_harga);
        return $data;
    }
}

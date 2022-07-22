<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;
use DB;

class BarangSimple extends Resource
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
            'kode_barang' => $this->kode_barang,
            'barcode' => $this->barcode,
            'nama_barang' => $this->nama_barang,
            'berat' => $this->berat,
            'isi' => $this->isi,
            'satuan' => $this->satuan,
            // 'id_segmen' => $this->id_segmen,
            'deskripsi' => $this->deskripsi,
            'gambar' => $this->gambar,
            
            // 'dbp', 'rbp', 'hcobp', 'wbp', 'cbp', 'lka', 'nka'

            // 'dbp' => DB::table('harga_barang')->select('harga')->where('id_barang', $this->id)
            //     ->where('tipe_harga', 'dbp')->latest()->first(),

            'rbp' => DB::table('harga_barang')->select('harga')->where('id_barang', $this->id)
                ->where('tipe_harga', 'rbp')->latest()->first(),

            'hcobp' => DB::table('harga_barang')->select('harga')->where('id_barang', $this->id)
                ->where('tipe_harga', 'hcobp')->latest()->first(),

            'wbp' => DB::table('harga_barang')->select('harga')->where('id_barang', $this->id)
                ->where('tipe_harga', 'wbp')->latest()->first(),
                
            'cbp' => DB::table('harga_barang')->select('harga')->where('id_barang', $this->id)
                ->where('tipe_harga', 'cbp')->latest()->first(),
                
            // // 'lka' => DB::table('harga_barang')->select('harga')->where('id_barang', $this->id)
            // //     ->where('tipe_harga', 'lka')->latest()->first(),

            // // 'nka' => DB::table('harga_barang')->select('harga')->where('id_barang', $this->id)
            // //     ->where('tipe_harga', 'nka')->latest()->first(),

            // 'created_by' => $this->created_by,
            // 'updated_by' => $this->updated_by,
            // // 'deleted_by' => $this->deleted_by,
            // 'created_at' => (string) $this->created_at,
            // 'updated_at' => (string) $this->updated_at,
            // // 'deleted_at' => (string) $this->deleted_at
        ];
    }
}

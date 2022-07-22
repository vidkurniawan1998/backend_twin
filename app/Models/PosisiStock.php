<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class PosisiStock extends Model
{
    // use SoftDeletes;

    protected $table = 'posisi_stock';

    protected $fillable = [
        'tanggal',
        'id_stock',
        'saldo_awal_qty',
        'saldo_awal_pcs',
        'pembelian_qty',
        'pembelian_pcs',
        'mutasi_masuk_qty',
        'mutasi_masuk_pcs',
        'adjustment_qty',
        'adjustment_pcs',
        'penjualan_qty',
        'penjualan_pcs',
        'mutasi_keluar_qty',
        'mutasi_keluar_pcs',
        'saldo_akhir_qty',
        'saldo_akhir_pcs',
        'harga',
        // 'nilai_stock',
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function stock(){
        return $this->belongsTo('App\Models\Stock', 'id_stock')->withTrashed();
    }

    // public function barang(){
    //     return $this->belongsTo('App\Models\Barang', 'id_barang')->withTrashed();
    // }

    public function getNilaiStockAttribute(){
        $nilai_stock = $this->harga * ($this->saldo_akhir_qty + ($this->saldo_akhir_pcs / $this->isi));
        return $nilai_stock;
    }

    public function getIdBarangAttribute()
    {
        return $this->stock->id_barang;
    }

    public function getNamaBrandAttribute()
    {
        $brand = optional($this->stock->barang->segmen)->brand;
        if ($brand) {
            return $brand->nama_brand;
        }

        return '';
    }

    public function getIdBrandAttribute()
    {
        $brand = optional($this->stock->barang->segmen)->brand;
        if ($brand) {
            return $brand->id;
        }

        return '';
    }

    public function getIdPrincipalAttribute()
    {
        $brand = optional($this->stock->barang->segmen)->brand;
        if ($brand) {
            return $brand->id_principal;
        }

        return '';
    }

    public function getIdGudangAttribute()
    {
        return $this->stock->id_gudang;
    }

    public function getNamaGudangAttribute()
    {
        return optional($this->stock->gudang)->nama_gudang;
    }

    public function getKodeBarangAttribute()
    {
        return optional($this->stock->barang)->kode_barang;
    }

    public function getIsiAttribute()
    {
        return optional($this->stock->barang)->isi;
    }

    public function getNamaBarangAttribute()
    {
        return optional($this->stock->barang)->nama_barang;
    }

    public function getNamaSegmenAttribute()
    {
        return optional($this->stock->barang->segmen)->nama_segmen;
    }


}

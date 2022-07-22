<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pengiriman extends Model
{
    use SoftDeletes;

    protected $table = 'pengiriman';

    protected $fillable = [
        'id_gudang',
        'id_driver',
        'id_kendaraan',
        'tgl_pengiriman',
        'status',
        'keterangan',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function gudang(){
        return $this->belongsTo('App\Models\Gudang', 'id_gudang')->withTrashed();
    }

    public function driver(){
        return $this->belongsTo('App\Models\Driver', 'id_driver');
    }

    public function kendaraan(){
        return $this->belongsTo('App\Models\Kendaraan', 'id_kendaraan')->withTrashed();
    }

    public function penjualan(){
        return $this->hasMany('App\Models\Penjualan', 'id_pengiriman');
    }

    public function detail_pengiriman(){
        return $this->hasMany('App\Models\DetailPenjualan', 'id_pengiriman');
    }

    public function detail_pengeluaran_barang(){
        return $this->hasMany('App\Models\DetailPengeluaranBarang', 'id_pengiriman');
    }

    // public function getNamaGudangAttribute()
    // {
    //     return $this->gudang->nama_gudang;
    // }

    // public function getTotalQtyAttribute()
    // {
    //     return $this->detail_penjualan->sum('qty');
    // }

    // public function getTotalPcsAttribute()
    // {
    //     return $this->detail_penjualan->sum('qty_pcs');
    // }

}
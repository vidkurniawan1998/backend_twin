<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class DetailPengeluaranBarang extends Model
{
    // use SoftDeletes;

    protected $table = 'detail_pengeluaran_barang';

    protected $fillable = [
        // 'id_pengeluaran_barang',
        'id_pengiriman',
        'id_detail_penjualan',
        'id_stock',
        'qty',
        'qty_pcs',
        'keterangan',
        'created_by',
        'updated_by',
        // 'deleted_by'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        // 'deleted_at'
    ];

    public function pengeluaran_barang(){
        return $this->belongsTo('App\Models\PengeluaranBarang', 'id_pengeluaran_barang')->withTrashed();
    }

    public function stock(){
        return $this->belongsTo('App\Models\Stock', 'id_stock')->withTrashed();
    }

    public function getKodeBarangAttribute()
    {
        return $this->stock->barang->kode_barang;
    }
    
    public function getNamaBarangAttribute()
    {
        return $this->stock->barang->nama_barang;
    }
}

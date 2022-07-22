<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class DetailMutasiBarang extends Model
{
    // use SoftDeletes;

    protected $table = 'detail_mutasi_barang';

    protected $fillable = [
        'id_mutasi_barang',
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

    public function mutasi_barang(){
        return $this->belongsTo('App\Models\MutasiBarang', 'id_mutasi_barang')->withTrashed();
    }

    public function stock(){
        return $this->belongsTo('App\Models\Stock', 'id_stock')->withTrashed();
    }

    public function getIdBarangAttribute()
    {
        return $this->stock->barang->id;
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

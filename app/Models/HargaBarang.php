<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HargaBarang extends Model
{

    protected $table = 'harga_barang';

    protected $fillable = [
        'id_barang',
        'tipe_harga',
        'harga',
        'harga_non_ppn',
        'ppn',
        'ppn_value',
        'created_by'
    ];

    protected $casts = [
        'id_barang' => 'integer',
        'harga' => 'float'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function stock(){
        return $this->hasMany('App\Models\Stock', 'id_barang');
    }

    public function barang(){
        return $this->belongsTo('App\Models\Barang', 'id_barang')->withTrashed();
    }
}

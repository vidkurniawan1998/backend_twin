<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoBarang extends Model
{
    protected $table = 'promo_barang';
    public $timestamps = false;
    protected $fillable = [
        'promo_id',
        'barang_id',
        'volume',
        'bonus_pcs'
    ];

    protected $casts = [
        'volume' => 'integer',
        'bonus_pcs' => 'integer'
    ];

    public function promo(){
        return $this->belongsTo('App\Models\Promo', 'promo_id')->withTrashed();
    }

    public function barang(){
        return $this->belongsTo('App\Models\Barang', 'barang_id')->withTrashed()->select(['id', 'nama_barang', 'kode_barang', 'isi']);
    }

}

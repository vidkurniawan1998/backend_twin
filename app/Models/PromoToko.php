<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoToko extends Model
{
    protected $table = 'promo_toko';
    public $timestamps = false;

    protected $fillable = [
        'promo_id',
        'toko_id'
    ];

    public function promo(){
        return $this->belongsTo('App\Models\Promo', 'promo_id', 'id')->withTrashed();
    }

    public function toko(){
        return $this->belongsTo('App\Models\Toko', 'toko_id', 'id')->withTrashed()->select(['id', 'nama_toko', 'no_acc']);
    }

}

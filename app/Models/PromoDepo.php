<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoDepo extends Model
{
    protected $table = 'promo_depo';
    public $timestamps = false;

    protected $fillable = [
        'id_promo',
        'id_depo'
    ];


    public function promo(){
        return $this->belongsTo('App\Models\Promo', 'id_promo')->withTrashed();
    }

    public function depo(){
        return $this->belongsTo('App\Models\Depo', 'id_depo')->withTrashed()->select(['id', 'nama_depo']);
    }



}

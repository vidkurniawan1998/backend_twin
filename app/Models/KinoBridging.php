<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KinoBridging extends Model
{
    use SoftDeletes;
    protected $table    = "kino_bridging";
    protected $guarded  = [];

    public function produk_mapping()
    {
        return $this->hasOne('App\Models\ProdukMapping', 's_code', 'citem');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Kelurahan extends Model
{
    protected $guarded = [];
    public $timestamps = false;
    use QueryCacheable;
    public $cacheFor = 86400;
    protected static $flushCacheOnUpdate = true;

    protected $table = 'kelurahan';

    public function kecamatan()
    {
        // return $this->belongsTo('App\Models\Kecamatan');
        return $this->belongsTo('App\Models\Kecamatan', 'id_kecamatan');
    }

    public function toko(){
        return $this->hasMany('App\Models\Toko', 'id_kelurahan');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Kabupaten extends Model
{
    protected $guarded = [];
    public $timestamps = false;
    use QueryCacheable;
    public $cacheFor = 86400;
    protected static $flushCacheOnUpdate = true;

    protected $table = 'kabupaten';

    public function kecamatan()
    {
        // return $this->hasMany('App\Models\Kecamatan');
        return $this->hasMany('App\Models\Kecamatan', 'id_kabupaten');
    }

    public function provinsi()
    {
        // return $this->belongsTo('App\Models\Provinsi');
        return $this->belongsTo('App\Models\Provinsi', 'id_provinsi');
    }
}

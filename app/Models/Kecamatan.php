<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Kecamatan extends Model
{
    protected $guarded = [];
    public $timestamps = false;
    use QueryCacheable;
    public $cacheFor = 86400;
    protected static $flushCacheOnUpdate = true;

    protected $table = 'kecamatan';

    public function kelurahan()
    {
        // return $this->hasMany('App\Models\Kelurahan');
        return $this->hasMany('App\Models\Kelurahan', 'id_kecamatan');
    }

    public function kabupaten()
    {
        // return $this->belongsTo('App\Models\Kabupaten');
        return $this->belongsTo('App\Models\Kabupaten', 'id_kabupaten');
    }
}

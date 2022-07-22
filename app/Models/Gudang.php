<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Gudang extends Model
{
    use SoftDeletes;
    use QueryCacheable;
    public $cacheFor = 86400;
    protected static $flushCacheOnUpdate = true;

    protected $table = 'gudang';

    protected $fillable = [
        'id_depo',
        'nama_gudang',
        'kode_gudang',
        'keterangan',
        'jenis',
        'keterangan',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function stock(){
        return $this->hasMany('App\Models\Stock', 'id_gudang');
    }

    public function depo(){
        return $this->belongsTo('App\Models\Depo', 'id_depo');
    }

    public function user() {
        return $this->belongsToMany('App\Models\Gudang', 'user_gudang');
    }
}

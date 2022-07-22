<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Principal extends Model
{
    use SoftDeletes;
    use QueryCacheable;
    public $cacheFor = 86400;
    protected static $flushCacheOnUpdate = true;

    protected $table = 'principal';

    protected $fillable = [
        'nama_principal',
        'alamat',
        'kode_pos',
        'telp',
        'id_perusahaan',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function penerimaan_barang(){
        return $this->hasMany('App\Models\PenerimaanBarang', 'id_principal');
    }
 
    public function perusahaan() {
        return $this->belongsTo('App\Models\Perusahaan', 'id_perusahaan', 'id');
    }

    public function users() {
        return $this->belongsToMany('App\Models\User', 'user_principal');
    }
}

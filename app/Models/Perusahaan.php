<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Perusahaan extends Model
{
    use SoftDeletes, QueryCacheable;
    public $cacheFor = 86400;
    protected static $flushCacheOnUpdate = true;
    protected $table = 'perusahaan';
    protected $fillable = [
        'kode_perusahaan', 
        'nama_perusahaan',
        'npwp',
        'nama_pkp',
        'alamat_pkp'
    ];

}

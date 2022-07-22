<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Brand extends Model
{
    use SoftDeletes;
    use QueryCacheable;
    public $cacheFor = 86400;
    protected static $flushCacheOnUpdate = true;

    protected $table = 'brand';

    protected $fillable = [
        'nama_brand',
        'id_principal',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function segmen(){
        return $this->hasMany('App\Models\Segmen', 'id_brand');
    }

    public function principal(){
        return $this->belongsTo('App\Models\Principal', 'id_principal')->withTrashed();
    }

    public function getNamaPrincipalAttribute(){
        if($this->id_principal){
            return $this->principal->nama_principal;
        }
        return null;
    }

}

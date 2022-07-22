<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Segmen extends Model
{
    use SoftDeletes;
    use QueryCacheable;
    public $cacheFor = 86400;
    protected static $flushCacheOnUpdate = true;

    protected $table = 'segmen';

    protected $fillable = [
        'nama_segmen',
        'id_brand',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // public function segmen(){
    //     return $this->hasMany('App\Models\Segmen', 'id_brand');
    // }

    public function brand(){
        return $this->belongsTo('App\Models\Brand', 'id_brand')->withTrashed();
    }

    public function getNamaBrandAttribute(){
        if($this->id_brand){
            return $this->brand->nama_brand;
        }
        return null;
    }

    public function getIdPrincipalAttribute(){
        if($this->id_brand){
            return $this->brand->id_principal;
        }
        return null;
    }

    public function getNamaPrincipalAttribute(){
        if($this->id_principal){
            if($this->brand->id_principal){
                return $this->brand->principal->nama_principal;
            }
        }
        return null;
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Depo extends Model
{
    use SoftDeletes;
    use QueryCacheable;
    public $cacheFor = 86400;
    protected static $flushCacheOnUpdate = true;

    protected $table = 'depo';

    protected $fillable = [
        'kode_depo',
        'nama_depo',
        'id_perusahaan',
        'id_gudang',
        'id_gudang_bs',
        'id_gudang_tg',
        'id_gudang_banded',
        'alamat',
        'telp',
        'fax',
        'kabupaten',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'id_perusahaan' => 'integer'
    ];

    // protected $appends = [
    //     'nama_gudang',
    //     'nama_gudang_bs',
    //     'nama_gudang_tg',
    //     'nama_gudang_banded'
    // ];

    public function gudang(){
        return $this->belongsTo('App\Models\Gudang', 'id_gudang')->withTrashed();
    }

    public function gudang_bs(){
        return $this->belongsTo('App\Models\Gudang', 'id_gudang_bs')->withTrashed();
    }

    public function gudang_tg(){
        return $this->belongsTo('App\Models\Gudang', 'id_gudang_tg')->withTrashed();
    }

    public function gudang_banded(){
        return $this->belongsTo('App\Models\Gudang', 'id_gudang_banded')->withTrashed();
    }

    public function penjualan() {
        return $this->hasMany('App\Models\Penjualan', 'id_depo', 'id');
    }

    public function retur_penjualan() {
        return $this->hasMany('App\Models\ReturPenjualan', 'id_depo', 'id');
    }

    public function getNamaGudangAttribute(){
        if($this->id_gudang){
            return $this->gudang->nama_gudang;
        }
        return null;
    }

    public function getNamaGudangBsAttribute(){
        if($this->id_gudang_bs){
            return $this->gudang_bs->nama_gudang;
        }
        return null;
    }

    public function getNamaGudangTgAttribute(){
        if($this->id_gudang_tg){
            return $this->gudang_tg->nama_gudang;
        }
        return null;
    }

    public function getNamaGudangBandedAttribute(){
        if($this->id_gudang_banded){
            return $this->gudang_banded->nama_gudang;
        }
        return null;
    }

    public function users() {
        return $this->belongsToMany('App\Models\User', 'user_depo');
    }

    public function promo() {
        return $this->belongsToMany(Promo::class, 'promo_depo');
    }

    public function barang() {
        return $this->belongsToMany('App\Models\Barang', 'barang_depo');
    }

    public function setKabupatenAttribute($value)
    {
        $this->attributes['kabupaten'] = strtoupper($value);
    }

    public function perusahaan()
    {
        return $this->belongsTo('App\Models\Perusahaan', 'id_perusahaan', 'id');
    }
}

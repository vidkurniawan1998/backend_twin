<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KepalaGudang extends Model
{

    protected $table = 'kepala_gudang';

    protected $primaryKey = 'user_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'id_gudang',
    ];

    // protected $appends = ['nama_kepala_gudang','nama_gudang'];

    public function user(){
        return $this->belongsTo('App\Models\User', 'user_id')->withTrashed();
    }

    public function gudang(){
        return $this->belongsTo('App\Models\Gudang', 'id_gudang')->withTrashed();
    }

    public function getNamaGudangAttribute(){
        if($this->id_gudang != null){
            return $this->gudang->nama_gudang;
        }
        return null;
    }

    public function getNamaKepalaGudangAttribute(){
        return $this->user->name;
    }

    public function getJenisGudangAttribute(){
        if($this->id_gudang != null){
            return $this->gudang->jenis;
        }
        return null;
    }

    public function getKeteranganGudangAttribute(){
        if($this->id_gudang != null){
            return $this->gudang->keterangan;
        }
        return null;
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Toko extends Model
{
    use SoftDeletes;

    protected $table = 'toko';

    protected $fillable = [
        'id',
        'nama_toko',
        'tipe',
        'tipe_2',
        'tipe_3',
        'tipe_harga',
        'pemilik',
        'no_acc',
        'cust_no',
        'kode_mars',
        'telepon',
        'alamat',
        'latitude',
        'longitude',
        'kode_pos',
        'id_kelurahan',
        'id_user',
        'created_by',
        'updated_by',
        'deleted_by',
        'status_verifikasi',
        'id_depo',
        'id_principal',
        'kode_eksklusif',
        'id_grup_logistik',
        'lock_order'
        // 'id_mitra'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function ketentuan_toko()
    {
        return $this->hasOne('App\Models\KetentuanToko', 'id_toko');
    }

    public function depo()
    {
        return $this->belongsTo('App\Models\Depo', 'id_depo');
    }

    public function kelurahan()
    {
        return $this->belongsTo('App\Models\Kelurahan', 'id_kelurahan');
    }

    public function principal()
    {
        return $this->belongsTo('App\Models\Principal', 'id_principal', 'id');
    }

    public function mitra()
    {
        return $this->belongsTo('App\Models\Mitra', 'id_mitra', 'id');
    }

    public function penjualan()
    {
        return $this->hasMany('App\Models\penjualan', 'id_toko', 'id');
    }

    public function getIdTimAttribute()
    {
        return $this->ketentuan_toko->id_tim;
    }

    public function getNamaTimAttribute()
    {
        if($this->id_tim == null){
            return null;
        }
        return $this->ketentuan_toko->tim->nama_tim ?? '';
    }

    public function getNamaDepoAttribute()
    {
        if($this->id_tim == null){
            return null;
        }
        return $this->ketentuan_toko->tim->depo->nama_depo;
    }

    public function getKTAttribute()
    {
        return $this->ketentuan_toko->k_t;
    }

    public function getLimitAttribute()
    {
        return $this->ketentuan_toko->limit;
    }

    public function getTopAttribute()
    {
        return $this->ketentuan_toko->top;
    }

    public function getNpwpAttribute()
    {
        return $this->ketentuan_toko->npwp;
    }

    public function getMingguAttribute()
    {
        return $this->ketentuan_toko->minggu;
    }

    public function getHariAttribute()
    {
        return $this->ketentuan_toko->hari;
    }

    public function getKabupatenAttribute()
    {
        if($this->id_kelurahan == null){
            return null;
        }
        return ucwords(strtolower($this->kelurahan->kecamatan->kabupaten->nama_kabupaten));
    }

    public function getKecamatanAttribute()
    {
        if($this->id_kelurahan == null){
            return null;
        }
        return ucwords(strtolower($this->kelurahan->kecamatan->nama_kecamatan));
    }

    public function getNamaKelurahanAttribute()
    {
        if($this->id_kelurahan == null){
            return null;
        }
        return ucwords(strtolower($this->kelurahan->nama_kelurahan));
    }

    public function getNamaPkpAttribute()
    {
        return $this->ketentuan_toko->nama_pkp;
    }
}

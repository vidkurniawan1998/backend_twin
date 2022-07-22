<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class KetentuanToko extends Model
{
    // use SoftDeletes;

    protected $table = 'ketentuan_toko';

    protected $primaryKey = 'id_toko';

    public $timestamps = false;

    protected $fillable = [
        'id_toko',
        'k_t',
        'top',
        'limit',
        'minggu',
        'hari',
        'id_tim',
        'npwp',
        'nama_pkp',
        'alamat_pkp',
        'no_ktp',
        'nama_ktp',
        'alamat_ktp'
    ];

    protected $appends = ['nama_toko'];

    public function toko(){
        return $this->belongsTo('App\Models\Toko', 'id_toko');
    }

    public function tim(){
        return $this->belongsTo('App\Models\Tim', 'id_tim')->withTrashed();
    }

    public function getNamaTokoAttribute(){
        return $this->toko->nama_toko;
    }
}

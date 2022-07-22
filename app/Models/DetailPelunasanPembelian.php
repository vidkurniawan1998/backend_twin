<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;

class DetailPelunasanPembelian extends Model
{
    use SoftDeletes;

    protected $table = 'detail_pelunasan_pembelian';

    protected $fillable = [
        'id_faktur_pembelian',
        'tanggal',
        'tipe',
        'nominal',
        'status',
        'bank',
        'no_rekening',
        'no_bg',
        'jatuh_tempo_bg',
        'keterangan',
        'approved_by',
        'created_by',
        'updated_by',
        'deleted_by',
        'approved_at',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $dates = [
        'approved_at',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function faktur_pembelian(){
        return $this->belongsTo('App\Models\FakturPembelian', 'id_faktur_pembelian', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'created_by', 'id');
    }
}

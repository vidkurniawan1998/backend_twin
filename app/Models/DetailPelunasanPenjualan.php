<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;

class DetailPelunasanPenjualan extends Model
{
    use SoftDeletes;

    protected $table = 'detail_pelunasan_penjualan';

    protected $fillable = [
        'id_penjualan',
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

    public function penjualan(){
        return $this->belongsTo('App\Models\Penjualan', 'id_penjualan')->withTrashed();
    }
}

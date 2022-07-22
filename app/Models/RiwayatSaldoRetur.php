<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatSaldoRetur extends Model
{
    protected $table = 'riwayat_saldo_retur';

    protected $fillable = [            
        'id_toko',
        'saldo_awal',
        'saldo_akhir',
        'keterangan',
        'id_retur_penjualan'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function toko()
    {
        return $this->belongsTo('App\Models\Toko', 'id_toko')->withTrashed();
    }

    public function retur_penjualan()
    {
        return $this->belongsTo('App\Models\ReturPenjualan', 'id_retur_penjualan')->withTrashed();
    }

}

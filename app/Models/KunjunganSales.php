<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KunjunganSales extends Model
{
    protected $table = 'kunjungan_sales';
    protected $fillable = ['id_toko', 'id_user', 'status', 'keterangan', 'latitude', 'longitude', 'id_perusahaan', 'id_depo'];
    protected $casts = [
        'id_toko' => 'integer',
        'id_user' => 'integer',
        'id_perusahaan' => 'integer',
        'id_depo' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float'
    ];

    public function toko()
    {
        return $this->belongsTo('App\Models\Toko', 'id_toko', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'id_user', 'id');
    }

    public function depo()
    {
        return $this->belongsTo('App\Models\Depo', 'id_depo', 'id');
    }

    public function perusahaaan()
    {
        return $this->belongsTo('App\Models\Perusahaan', 'id_perusahaan', 'id');
    }
}

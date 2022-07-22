<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HariEfektif extends Model
{

    protected $table = 'hari_efektif';

    protected $fillable = [
        'tanggal',
        'minggu',
        'bulan'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatPenagihan extends Model
{
    protected $table = 'riwayat_rekapitulasi_penagihan';

    protected $fillable = [ 
    	'tanggal_penagihan',           
        'id_salesman',
        'id_penjualan',
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

}

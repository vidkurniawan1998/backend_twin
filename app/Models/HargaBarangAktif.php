<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HargaBarangAktif extends Model
{

    protected $table = 'harga_barang_aktif';

    protected $fillable = [
        'id_barang',
        'id_harga_barang',
        'tipe_harga',
        'harga',
        'harga_non_ppn',
        'ppn',
        'ppn_value',
        'created_by'
    ];

    protected $casts = [
        'id_barang' => 'integer',
        'harga' => 'float'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

}

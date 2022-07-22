<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NpwpExternal extends Model
{

    protected $table = 'npwp_external';

    protected $fillable = [
        'kode_outlet',
        'nama_toko',
        'npwp',
        'nama_pkp',
        'alamat_pkp'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];
}
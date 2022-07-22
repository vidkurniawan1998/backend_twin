<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kendaraan extends Model
{
    use SoftDeletes;

    protected $table = 'kendaraan';

    protected $fillable = [
        'no_pol_kendaraan',
        'jenis',
        'merk',
        'body_no',
        'tahun',
        'samsat',
        'peruntukan',
        'keterangan',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

}
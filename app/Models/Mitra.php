<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Mitra extends Model
{
    protected $table = 'mitra';
    protected $fillable = [
        'kode_mitra',
        'perusahaan',
        'alamat',
        'telp',
        'fax',
        'kabupaten',
        'minimal_order'
    ];
}

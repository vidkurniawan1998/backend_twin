<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class RiwayatTop extends Model
{
    protected $table = 'riwayat_top';
    protected $fillable = ['id_toko', 'top', 'update_by'];
    protected $casts = [
        'id_toko'   => 'integer',
        'top'       => 'float',
        'update_by' => 'integer'
    ];
}
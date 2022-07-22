<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class RiwayatLimitKredit extends Model
{
    protected $table    = 'riwayat_limit_kredit';
    protected $fillable = ['id_toko', 'limit_credit', 'update_by'];
    protected $casts = [
        'id_toko'       => 'integer',
        'limit_credit'  => 'float',
        'update_by'     => 'integer'
    ];
}
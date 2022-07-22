<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class ImportKino extends Model
{
    protected $table = 'import_kino';
    protected $fillable = [
        'txt_name',
        'no_reff',
        'cust_no_to',
        'sls_no',
        'tanggal',
        'time_in',
        'p_code',
        'qty',
        'sell_price',
        'top',
        'flag_noo',
        'cabang',
        'kode_diskon',
        'diskon_percent',
        'diskon_value',
        'kode_promo',
        'promo_value',
        'promo_percent',
        'flag_promo'
    ];
}
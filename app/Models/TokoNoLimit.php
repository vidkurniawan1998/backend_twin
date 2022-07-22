<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class TokoNoLimit extends Model
{
    protected $table = 'toko_no_limit';
    protected $fillable = ['id_toko', 'tipe', 'created_by'];

    public function toko()
    {
        return $this->belongsTo('App\Models\Toko', 'id_toko', 'id');
    }
}

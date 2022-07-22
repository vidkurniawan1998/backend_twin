<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class GrupTokoLogistik extends Model
{
    protected $table = 'grup_toko_logistik';
    protected $fillable = ['nama_grup', 'created_by'];

    public function toko() {
        return $this->hasMany('App\Models\Toko', 'id_grup_logistik', 'id');
    }
}
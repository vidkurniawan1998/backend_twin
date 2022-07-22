<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provinsi extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    protected $table = 'provinsi';

    public function kabupaten()
    {
        // return $this->hasMany('App\Models\Kabupaten');
        return $this->hasMany('App\Models\Kabupaten', 'id_provinsi');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipeHarga extends Model
{
    use SoftDeletes;
    protected $table = 'tipe_harga';
    protected $guarded = [];

    public function perusahaan()
    {
        return $this->belongsToMany(Perusahaan::class, 'tipe_harga_perusahaan', 'id_tipe_harga', 'id_perusahaan');
    }
}

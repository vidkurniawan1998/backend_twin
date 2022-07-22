<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogStock extends Model
{
    use SoftDeletes;
    protected $table = 'log_stock';
    protected $fillable = ['tanggal', 'id_barang', 'id_gudang', 'id_user', 'id_referensi', 'referensi', 'no_referensi', 'qty_pcs', 'parameter', 'status'];
}

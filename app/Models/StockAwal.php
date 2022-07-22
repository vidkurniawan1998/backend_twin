<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class StockAwal extends Model
{
    // use SoftDeletes;

    protected $table = 'stock_awal';

    protected $fillable = [
        'tanggal',
        'id_stock',
        'qty_stock',
        'qty_pcs_stock',
        'qty_pending',
        'qty_pcs_pending',
        'harga',
        'qty_mutasi_pending',
        'qty_pcs_mutasi_pending'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function stock()
    {
        return $this->belongsTo('App\Models\Stock', 'id_stock')->withTrashed();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailStockOpname extends Model
{
    protected $table = 'detail_stock_opname';

    protected $fillable = [
        'id_stock_opname',
        'id_stock',
        'qty',
        'qty_pcs',
        'qty_fisik',
        'qty_pcs_fisik'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];
}

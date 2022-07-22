<?php

namespace App\Models;

use App\Traits\DepoLocationTrait;
use Illuminate\Database\Eloquent\Model;

class StockBridging extends Model
{
    use DepoLocationTrait;
    protected $table = 'stock_bridging';
    protected $guarded = [];
}
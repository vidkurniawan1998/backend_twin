<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanHeader extends Model
{
    protected $table = 'penjualan_header';
    protected $fillable = ['no_invoice'];
}
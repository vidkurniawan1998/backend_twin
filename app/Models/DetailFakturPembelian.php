<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailFakturPembelian extends Model
{
    protected $table = 'detail_faktur_pembelian';
    protected $fillable = [
        'id_faktur_pembelian',
        'id_barang',
        'qty',
        'pcs',
        'harga',
        'disc_persen',
        'disc_value'
    ];
    protected $casts = [
        'id_faktur_pembelian'   => 'int',
        'id_barang'             => 'int',
        'qty'                   => 'int',
        'pcs'                   => 'int',
        'harga'                 => 'float',
        'disc_persen'           => 'float',
        'disc_value'            => 'float'
    ];

    public function barang()
    {
        return $this->belongsTo('App\Models\Barang', 'id_barang', 'id');
    }

    public function faktur_pembelian()
    {
        return $this->belongsTo('App\Models\FakturPembelian', 'id_faktur_pembelian', 'id');
    }

    public function getInCartonAttribute()
    {
        return $this->qty + ($this->pcs / $this->barang->isi);
    }

    public function getSubtotalAttribute()
    {
        return $this->in_carton * $this->harga;
    }

    public function getDiscountAttribute()
    {
        $nominal = $this->disc_value;
        $percent = $this->disc_persen / 100 * $this->subtotal;
        return $nominal + $percent;
    }

    public function getDppAttribute()
    {
        return $this->subtotal - $this->discount;
    }
}

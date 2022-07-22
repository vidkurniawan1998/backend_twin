<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class PenjualanPajak extends Model
{
    protected $table = 'penjualan_pajak';
    protected $fillable = ['id_penjualan', 'npwp', 'nama_pkp', 'alamat_pkp'];

    public function penjualan()
    {
        return $this->belongsTo('App\Models\Penjualan', 'id_penjualan', 'id');
    }
}

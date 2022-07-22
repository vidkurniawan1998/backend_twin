<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\HargaBarang;
use App\Models\DetailPenjualan;
Use DB;

class Stock extends Model
{
    use SoftDeletes;

    protected $table = 'stock';

    protected $fillable = [
        'id_gudang',
        'id_barang',
        'qty',
        'qty_pcs',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function gudang(){
        return $this->belongsTo('App\Models\Gudang', 'id_gudang')->withTrashed();
    }

    public function barang(){
        return $this->belongsTo('App\Models\Barang', 'id_barang')->withTrashed();
    }

    public function getKodeBarangAttribute()
    {
        return $this->barang->kode_barang;
    }
    
    public function getNamaBarangAttribute()
    {
        return $this->barang->nama_barang;
    }
    
    public function getNamaSegmenAttribute()
    {
        return optional($this->barang->segmen)->nama_segmen;
    }

    // public function getQtyAvailableAttribute()
    // {
    //     $qty_available = 100000;

    //     return $qty_available;
    // }
    
    public function getQtyAvailableAttribute()
    {
        $pending = DetailPenjualan::where('id_stock', $this->id)->whereHas('penjualan', function ($query) {
            $query->where('status', 'waiting');
        })->sum('qty');

        $qty_available = $this->qty - $pending;

        return $qty_available;
    }

    public function getDbpAttribute(){
        $dbp = HargaBarang::where('tipe_harga', 'dbp')->where('id_barang', $this->barang->id)->latest()->value('harga');
        if(!$dbp){
            $dbp = 0;
        }
        return $dbp;
    }

    public function getIsiAttribute(){
        return $this->barang->isi;
    }

}
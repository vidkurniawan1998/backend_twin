<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Barang extends Model
{
    use SoftDeletes;
    use QueryCacheable;
    public $cacheFor = 86400;
    protected static $flushCacheOnUpdate = true;

    protected $table = 'barang';

    protected $fillable = [
        'id',
        'kode_barang',
        'item_code',
        'pcs_code',
        'barcode',
        'nama_barang',
        'berat',
        'isi',
        'kelipatan_order',
        'satuan',
        'id_segmen',
        'deskripsi',
        'gambar',
        'created_by',
        'updated_by',
        'deleted_by',
        'extra',
        'status',
        'tipe',
        'id_perusahaan',
        'id_mitra'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'id_segmen' => 'integer',
        'id_perusahaan' => 'integer',
        'berat' => 'float',
        'isi' => 'integer'
    ];

    public function stock(){
        return $this->hasMany('App\Models\Stock', 'id_barang');
    }

    public function harga_barang(){
        return $this->hasMany('App\Models\HargaBarang', 'id_barang');
    }

    public function segmen(){
        return $this->belongsTo('App\Models\Segmen', 'id_segmen')->withTrashed();
    }

    public function getNamaSegmenAttribute(){
        if($this->id_segmen != ''){
            return $this->segmen->nama_segmen;
        }
        return null;
    }

    public function getNamaBrandAttribute(){
        if($this->id_segmen != ''){
            return $this->segmen->brand->nama_brand;
        }
        return null;
    }

    public function getDbpAttribute(){
        $dbp = HargaBarang::where('tipe_harga', 'dbp')->where('id_barang', $this->id)->latest()->value('harga');
        if(!$dbp){
            $dbp = 0;
        }
        return $dbp;
    }

    public function depo() {
        return $this->belongsToMany('App\Models\Depo', 'barang_depo');
    }

    public function promo() {
        return $this->belongsToMany(Promo::class, 'promo_barang', 'barang_id', 'promo_id');
    }

    public function perusahaan()
    {
        return $this->belongsTo('App\Models\Perusahaan', 'id_perusahaan', 'id');
    }
}

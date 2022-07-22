<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promo extends Model
{
    use SoftDeletes;

    protected $table = 'promo';

    protected $fillable = [
        // 'kode_promo',
        'nama_promo',
        'no_promo',
        'status',
        'status_klaim',
        'keterangan',
        'disc_persen',
        'disc_1',
        'disc_2',
        'disc_3',
        'disc_4',
        'disc_5',
        'disc_6',
        'tanggal_awal',
        'tanggal_akhir',
        'disc_rupiah',
        'id_barang',
        'volume_extra',
        'pcs_extra',
        'created_by',
        'updated_by',
        'deleted_by',
        'id_perusahaan',
        'salesman',
        'disc_rupiah_distributor',
        'disc_rupiah_principal',
        'id_principal',
        'minimal_order'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'disc_1'        => 'float',
        'disc_2'        => 'float',
        'disc_3'        => 'float',
        'disc_4'        => 'float',
        'disc_5'        => 'float',
        'disc_6'        => 'float',
        'disc_persen'   => 'float',
        'disc_rupiah'   => 'integer',
        'pcs_extra'     => 'integer',
        'volume_extra'  => 'integer',
        'id_perusahaan' => 'integer',
        'disc_rupiah_distributor' => 'integer',
        'disc_rupiah_principal' => 'integer',
        'minimal_order' => 'integer'
    ];

    public function barang(){
        return $this->belongsTo('App\Models\Barang', 'id_barang')->withTrashed();
    }

    public function promo_toko(){
        return $this->belongsToMany(Toko::class, 'promo_toko', 'promo_id', 'toko_id');
    }

    public function promo_barang(){
        return $this->belongsToMany(Barang::class, 'promo_barang', 'promo_id', 'barang_id')->withPivot('volume', 'bonus_pcs');
    }

    public function depo(){
        return $this->belongsToMany(Depo::class, 'promo_depo');
    }

    public function perusahaan()
    {
        return $this->belongsTo('App\Models\Perusahaan', 'id_perusahaan', 'id');
    }
}

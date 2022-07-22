<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MutasiBarang extends Model
{
    use SoftDeletes;

    protected $table = 'mutasi_barang';

    protected $fillable = [
        'tanggal_mutasi',
        'dari',
        'ke',
        'keterangan',
        'is_approved',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function detail_mutasi_barang(){
        return $this->hasMany('App\Models\DetailMutasiBarang', 'id_mutasi_barang');
    }

    public function dari_gudang(){
        return $this->belongsTo('App\Models\Gudang', 'dari')->withTrashed();
    }

    public function ke_gudang(){
        return $this->belongsTo('App\Models\Gudang', 'ke')->withTrashed();
    }

    public function getTotalQtyAttribute(){
        return $this->detail_mutasi_barang->sum('qty');
    }
    public function getTotalPcsAttribute(){
        return $this->detail_mutasi_barang->sum('qty_pcs');
    }
}

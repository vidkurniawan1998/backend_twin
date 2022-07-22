<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Adjustment extends Model
{
    use SoftDeletes;

    protected $table = 'adjustment';

    protected $fillable = [
        'no_adjustment',
        'id_gudang',
        'tanggal',
        'status',
        'keterangan',
        'created_by', // pic
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

    public function detail_adjustment(){
        return $this->hasMany('App\Models\Adjustment', 'id_adjustment');
    }

    public function pic(){
        return $this->belongsTo('App\Models\User', 'created_by')->withTrashed();
    }

}

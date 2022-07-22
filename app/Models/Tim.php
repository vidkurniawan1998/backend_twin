<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tim extends Model
{
    use SoftDeletes;

    protected $table = 'tim';

    protected $fillable = [
        'nama_tim',
        'tipe',
        'id_depo',
        'id_sales_supervisor',
        'id_sales_koordinator',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function depo(){
        return $this->belongsTo('App\Models\Depo', 'id_depo')->withTrashed();
    }

    public function salesman(){
        return $this->hasOne('App\Models\Salesman', 'id_tim');
    }

    public function driver(){
        return $this->hasOne('App\Models\Driver', 'id_tim');
    }
    
    public function canvass(){
        return $this->hasOne('App\Models\Canvass', 'id_tim');
    }

    public function user_koordinator(){
        return $this->belongsTo('App\Models\User', 'id_sales_koordinator')->withTrashed();
    }

    public function user_supervisor(){
        return $this->belongsTo('App\Models\User', 'id_sales_supervisor')->withTrashed();
    }
}

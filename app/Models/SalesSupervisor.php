<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesSupervisor extends Model
{
    use SoftDeletes;

    protected $table = 'sales_supervisor';

    protected $primaryKey = 'user_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'keterangan',
        'deleted_by',
        'deleted_at',
    ];

    protected $dates = [
        'deleted_at'
    ];

    public function user(){
        return $this->belongsTo('App\Models\User', 'user_id')->withTrashed();
    }

    public function tim(){
        return $this->hasMany('App\Models\Tim', 'id_sales_supervisor');
    }

}
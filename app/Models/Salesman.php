<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\DetailPenjualan;

class Salesman extends Model
{
    use SoftDeletes;

    protected $table = 'salesman';

    protected $primaryKey = 'user_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'id_tim',
        'kode_eksklusif',
        'id_principal'
    ];

    protected $dates = [
        'deleted_at'
    ];

    public function user(){
        return $this->belongsTo('App\Models\User', 'user_id')->withTrashed();
    }

    public function tim(){
        return $this->belongsTo('App\Models\Tim', 'id_tim')->withTrashed();
    }

    public function penjualan(){
        return $this->hasMany('App\Models\Penjualan', 'id_salesman');
    }

    public function principal(){
        return $this->belongsTo('App\Models\Principal', 'id_principal', 'id');
    }

    public function getTipeSalesAttribute(){
        return $this->tim->tipe;
    }

    public function getNamaDepoAttribute(){
        return $this->tim->depo->nama_depo;
    }

    public function getEffectiveCallAttribute(){
        return $this->penjualan->whereIn('status', ['approved','loaded', 'delivered'])->count();
    }

    public function getStockKeepingUnitAttribute(){
        $count_sku = DetailPenjualan::whereHas('penjualan', function($q){
            $q->where('id_salesman', $this->user_id);
        })->distinct(['id_penjualan', 'id_stock'])->count();
        if($this->effective_call != 0){
            $sku = $count_sku / $this->effective_call;
        }
        else{
            $sku = 0;
        }
        return $sku;
    }

    public function getDropSizeAttribute(){
        $detail_penjualan = DetailPenjualan::whereHas('penjualan', function($q){
            $q->where('id_salesman', $this->user_id);
        })->get();
        // $sum_qty = $detail_penjualan->sum('qty');
        $carton = $detail_penjualan->sum('sum_carton');

        if($this->effective_call != 0){
            $ds = $carton / $this->effective_call;
        }
        else{
            $ds = 0;
        }

        return $ds;
    }
}
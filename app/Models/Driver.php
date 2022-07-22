<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Penjualan;
use Carbon\Carbon;

class Driver extends Model
{
    use SoftDeletes;

    protected $table = 'driver';
    protected $primaryKey = 'user_id';

//    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'deleted_by'
    ];

    protected $dates = [
        'deleted_at'
    ];

    public function user(){
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function penjualan(){
        return $this->belongsTo('App\Models\Penjualan', 'user_id','driver_id');
    }
    public function getJumlahInvoiceAttribute()
    {
        $jumlah = Penjualan::where('penjualan.driver_id',$this->user_id)
                            ->where('penjualan.status', 'approved')
                            ->whereDate('penjualan.tanggal_jadwal', Carbon::today())
                            ->count();
        return $jumlah;
    }

}

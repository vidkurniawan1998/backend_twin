<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Canvass extends Model
{
    protected $table = 'canvass';
    protected $primaryKey = 'id_tim';
    public $timestamps = false;

    protected $fillable = [
        'id_tim',
        'id_gudang_canvass',
        'id_kendaraan',
    ];

    public function tim(){
        return $this->belongsTo('App\Models\Tim', 'id_tim')->withTrashed();
    }

    public function gudang_canvass(){
        return $this->belongsTo('App\Models\Gudang', 'id_gudang_canvass')->withTrashed();
    }

    public function kendaraan(){
        return $this->belongsTo('App\Models\Kendaraan', 'id_kendaraan')->withTrashed();
    }

    // public function getNoPolKendaraanAttribute() {
    //     return $this->kendaraan->no_pol_kendaraan;
    // }

    // public function getBodyNoAttribute() {
    //     return $this->kendaraan->body_no;
    // }

    // public function getNamaTimAttribute(){
    //     return $this->tim->nama_tim;
    // }

    // public function getNamaDriverAttribute(){
    //     // return $this->tim->driver->user->name;
    //     return 'nama_driver';
    // }

    // public function getNamaSalesmanAttribute(){
    //     // return $this->tim->salesman->user->name;
    //     return 'nama_salesman';
    // }
}
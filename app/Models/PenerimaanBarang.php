<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PenerimaanBarang extends Model
{
    use SoftDeletes;

    protected $table = 'penerimaan_barang';

    protected $fillable = [
        'no_pb',
        'no_do',
        'no_spb',
        'id_principal',
        'id_gudang',
        'tgl_kirim',
        'tgl_datang',
        'tgl_bongkar',
        'driver',
        'transporter',
        'no_pol_kendaraan',
        'keterangan',
        'is_approved',
        'scan',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function detail_penerimaan_barang(){
        return $this->hasMany('App\Models\DetailPenerimaanBarang', 'id_penerimaan_barang');
    }

    public function principal(){
        return $this->belongsTo('App\Models\Principal', 'id_principal')->withTrashed();
    }

    public function gudang(){
        return $this->belongsTo('App\Models\Gudang', 'id_gudang')->withTrashed();
    }

    public function faktur_pembelian()
    {
        return $this->belongsToMany('App\Models\FakturPembelian', 'faktur_pembelian_penerimaan', 'id_penerimaan_barang', 'id_faktur_pembelian');
    }

    public function getTotalQtyAttribute()
    {
        return $this->detail_penerimaan_barang->sum('qty');
    }
    
    public function getTotalPcsAttribute()
    {
        return $this->detail_penerimaan_barang->sum('qty_pcs');
    }
    
    public function getWeekAttribute(){
        return \Carbon\Carbon::parse($this->created_at)->weekOfYear;
    }

    public function getSiklusAttribute(){
        return \Carbon\Carbon::parse($this->created_at)->month;
    }

    public function getPpnAttribute(){
        return $this->detail_penerimaan_barang->sum('ppn');
    }

    public function getSubtotalAttribute(){
        return $this->detail_penerimaan_barang->sum('subtotal');
    }

    public function getGrandTotalAttribute(){
        return $this->detail_penerimaan_barang->sum('grand_total');
    }
}
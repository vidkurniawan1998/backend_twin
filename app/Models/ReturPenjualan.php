<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturPenjualan extends Model
{
    use SoftDeletes;

    protected $table = 'retur_penjualan';

    protected $fillable = [
        'no_invoice',
        'no_retur_manual',
        'id_depo',
        'id_salesman',
        'id_tim',
        'id_toko',
        'npwp',
        'id_gudang',
        'tipe_retur',
        'tipe_barang',
        'sales_retur_date',
        'claim_date',
        'keterangan',
        'status',
        'saldo_retur',
        'created_by',
        'updated_by',
        'deleted_by',
        'approved_by',
        'verified_by',
        'approved_at',
        'verified_at',
        'id_mitra',
        'potongan',
        'faktur_pajak',
        'faktur_pajak_pembelian',
        'tanggal_faktur_pajak_pembelian',
    ];

    protected $casts = [
        'grand_total'           => 'float',
        'subtotal'              => 'float',
        'subtotal_after_tax'    => 'float',
        'discount'              => 'float',
        'discount_after_tax'    => 'float',
        'dpp'                   => 'float',
        'id_mitra'              => 'integer',
        'potongan'              => 'float'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'approved_at',
    ];

    public function salesman(){
        return $this->belongsTo('App\Models\Salesman', 'id_salesman')->withTrashed();
    }

    public function toko(){
        return $this->belongsTo('App\Models\Toko', 'id_toko')->withTrashed();
    }

    public function ketentuan_toko(){
        return $this->belongsTo('App\Models\KetentuanToko', 'id_toko', 'id_toko');
    }

    public function mitra()
    {
        return $this->belongsTo('App\Models\Mitra', 'id_mitra', 'id');
    }

    public function gudang(){
        return $this->belongsTo('App\Models\Gudang', 'id_gudang')->withTrashed();
    }

    public function detail_retur_penjualan(){
        return $this->hasMany('App\Models\DetailReturPenjualan', 'id_retur_penjualan');
    }

    public function pic(){
        return $this->belongsTo('App\Models\User', 'approved_by')->withTrashed();
    }

    public function depo() {
        return $this->belongsTo('App\Models\Depo', 'id_depo')->withTrashed();
    }

    public function tim() {
        return $this->belongsTo('App\Models\Tim', 'id_tim', 'id')->withTrashed();
    }

    public function user_verify()
    {
        return $this->belongsTo('App\Models\User', 'verified_by', 'id');
    }

    public function getTotalDusAttribute()
    {
        return $this->detail_retur_penjualan->sum('qty_dus');
    }

    public function getTotalPcsAttribute()
    {
        return $this->detail_retur_penjualan->sum('qty_pcs');
    }

    public function getSubtotalAttribute()
    {
        return $this->detail_retur_penjualan->sum('subtotal');
    }

    public function getSubtotalAfterTaxAttribute()
    {
        return $this->detail_retur_penjualan->sum('subtotal_after_tax');
    }

    public function getDiscountAttribute()
    {
        return $this->detail_retur_penjualan->sum('discount');
    }

    public function getDiscountAfterTaxAttribute()
    {
        return $this->detail_retur_penjualan->sum('discount_after_tax');
    }

    public function getDppAttribute()
    {
        return $this->detail_retur_penjualan->sum('dpp');
    }

    public function getGrandTotalAttribute()
    {
         return $this->dpp * 1.1;
    }

     public function getPpnAttribute()
     {
         return $this->detail_retur_penjualan->sum('ppn');
     }
}

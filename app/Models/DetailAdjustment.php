<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailAdjustment extends Model
{
    protected $table = 'detail_adjustment';

    protected $fillable = [
        'id_adjustment',
        'id_stock',
        'qty_adj',
        'pcs_adj',
        'id_harga',
        'created_by',
        'updated_by'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function adjustment(){
        return $this->belongsTo('App\Models\Adjustment', 'id_adjustment')->withTrashed();
    }

    public function stock(){
        return $this->belongsTo('App\Models\Stock', 'id_stock')->withTrashed();
    }

    public function harga_barang(){
        return $this->belongsTo('App\Models\HargaBarang', 'id_harga');
    }

    public function getNamaBarangAttribute(){
        return optional($this->stock->barang)->nama_barang;
    }

    public function getKodeBarangAttribute(){
        return optional($this->stock->barang)->kode_barang;
    }
    
    public function getSatuanAttribute(){
        return optional($this->stock->barang)->satuan;
    }

    public function getTipeHargaAttribute(){
        return optional($this->harga_barang)->tipe_harga;
    }

    public function getPriceAfterTaxAttribute(){
        return optional($this->harga_barang)->harga;
    }

    public function getPriceBeforeTaxAttribute(){
        $price_before_tax = optional($this->harga_barang)->harga / 1.1;
        return floatval($price_before_tax);
    }
    
    public function getSubtotalAttribute(){
        $isi = $this->stock->barang->isi;
        $subtotal = ($this->qty_adj + ($this->pcs_adj / $isi)) * $this->price_before_tax;
        return floatval($subtotal);
    }

    

}

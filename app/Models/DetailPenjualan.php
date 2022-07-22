<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\HargaBarang;

class DetailPenjualan extends Model
{
    protected $table = 'detail_penjualan';

    protected $fillable = [
        'id_penjualan',
        'id_stock',
        'qty',
        'qty_pcs',
        'order_qty',
        'order_pcs',
        'id_harga',
        'harga_dbp',
        'harga_jual',
        'id_promo',
        'disc_persen',
        'disc_rupiah',
        'kode_promo',
        'created_by',
        'updated_by',
        'qty_pcs_loading',
        'qty_loading'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'harga_dbp'     => 'float',
        'harga_jual'    => 'float',
        'disc_persen'   => 'float',
        'disc_rupiah'   => 'float'
    ];

    public function penjualan(){
        return $this->belongsTo('App\Models\Penjualan', 'id_penjualan')->withTrashed();
    }

    public function harga_barang(){
        return $this->belongsTo('App\Models\HargaBarang', 'id_harga');
    }

    public function stock(){
        return $this->belongsTo('App\Models\Stock', 'id_stock')->withTrashed();
    }

    public function promo(){
        return $this->belongsTo('App\Models\Promo', 'id_promo')->withTrashed();
    }

    public function barang(){
        return $this->belongsTo('App\Models\Barang', 'id_barang')->withTrashed();
    }

    public function getSumPcsAttribute(){
        return $this->qty * $this->stock->barang->isi + $this->qty_pcs;
    }

    public function getSumCartonAttribute(){
        $sum_carton = $this->qty + ($this->qty_pcs / $this->stock->barang->isi);
        return $sum_carton;
    }

    public function getTanggalAttribute(){
        return $this->penjualan->tanggal;
    }

    public function getIdSalesmanAttribute(){
        return $this->penjualan->id_salesman;
    }

    public function getStatusAttribute(){
        return $this->penjualan->status;
    }

    public function getIdBarangAttribute(){
        return optional($this->stock->barang)->id;
    }

    public function getKodeBarangAttribute(){
        return optional($this->stock->barang)->kode_barang;
    }

    public function getNamaBarangAttribute(){
        return optional($this->stock->barang)->nama_barang;
    }

    public function getNamaPromoAttribute(){
        if($this->id_promo == 0){
            $nama_promo = null;
        }
        else{
            $nama_promo = optional($this->promo)->nama_promo;
        }
        return $nama_promo;
    }

    public function getNamaSegmenAttribute(){
        return optional($this->stock->barang->segmen)->nama_segmen;
    }

    public function getNamaBrandAttribute(){
        return optional($this->stock->barang->segmen->brand)->nama_brand;
    }

    public function getPriceBeforeTaxAttribute(){
        return $this->harga_jual / 1.1;
    }

    public function getSubtotalAttribute(){
        $subtotal = $this->price_before_tax * $this->sum_carton;
        return floatval($subtotal);
    }

    public function getSubtotalAfterTaxAttribute(){
        $subtotal = $this->harga_jual * $this->sum_carton;
        return floatval($subtotal);
    }

    public function getDiscountAttribute(){
        $discount = 0;
        if ($this->id_promo) {
            $disc_rupiah= ($this->disc_rupiah / 1.1) * $this->sum_carton;
            $disc_persen= ($this->disc_persen / 100) * $this->subtotal;
            $discount   = $disc_rupiah + $disc_persen;
        }
        return $discount;
    }

    public function getDiscountAfterTaxAttribute(){
        $discount = 0;
        if ($this->id_promo) {
            $disc_rupiah = ($this->disc_rupiah) * $this->sum_carton;
            $disc_persen = ($this->disc_persen / 100) * $this->subtotal_after_tax;
            $discount = $disc_rupiah + $disc_persen;
        }
        return $discount;
    }

    public function getPpnAttribute(){
        $ppn = ($this->subtotal - $this->discount) / 10;
        return floatval($ppn);
    }

    public function getNetAttribute(){ // ganti istilah net dengan DPP
        $net = $this->subtotal - $this->discount;
        return floatval($net);
    }

    public function getDppAttribute(){
        $dpp = $this->subtotal - $this->discount;
        return floatval($dpp);
    }

    public function getSubtotalOrderAttribute(){
        if($this->harga_barang->id != 0){
            $qty_total = $this->order_qty + ($this->order_pcs / $this->harga_barang->barang->isi); //dlm jumlah dus dalam bentuk koma
        }
        else{
            $qty_total = 0;
        }

        $subtotal = ($this->harga_jual / 1.1) * $qty_total;

        return floatval($subtotal);
    }

    public function getTotalAttribute(){
        $total = $this->subtotal - $this->discount + $this->ppn;
        return floatval($total);
    }

    public function getNpwpAttribute(){
        $npwp = optional($this->penjualan->toko->ketentuan_toko)->npwp;
        return $npwp;
    }

    public function getNamaPkpAttribute(){
        $namaPKP = optional($this->penjualan->toko->ketentuan_toko)->nama_pkp;
        return $namaPKP;
    }

    public function getAlamatPkpAttribute(){
        $alamatPKP = optional($this->penjualan->toko->ketentuan_toko)->alamat_pkp;
        return $alamatPKP;
    }

    public function getDbpAttribute(){
        return floatval($this->harga_dbp / 1.1);
    }

    public function getHppAttribute(){
        $hpp = $this->dbp * $this->sum_carton;
        return floatval($hpp);
    }
    
    public function scopeRelationData($query) {
        return $query->with(['penjualan', 'stock', 'stock.barang', 'harga_barang', 'promo']);
    }

}

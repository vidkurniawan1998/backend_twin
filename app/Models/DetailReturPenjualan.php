<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class DetailReturPenjualan extends Model
{
    // use SoftDeletes;

    protected $table = 'detail_retur_penjualan';

    protected $fillable = [
        'id_retur_penjualan',
        'id_barang',
        'kategori_bs',
        'expired_date',
        // 'jml_pcs',
        'qty_dus_order',
        'qty_pcs_order',
        'qty_dus',
        'qty_pcs',
        'harga',
        'disc_persen',
        'disc_nominal',
        'value_retur_percentage',
        'subtotal',
        'created_by',
        'updated_by',
        'harga_dbp'
    ];

    protected $casts = [
        'disc_persen' => 'float',
        'disc_nominal' => 'integer',
        'harga' => 'float',
        'subtotal' => 'float',
        'discount_after_tax' => 'float',
        'discount' => 'float',
        'harga_dbp' => 'float',
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function retur_penjualan(){
        return $this->belongsTo('App\Models\ReturPenjualan', 'id_retur_penjualan')->withTrashed();
    }

    public function barang(){
        return $this->belongsTo('App\Models\Barang', 'id_barang')->withTrashed();
    }

    public function getNamaGudangAttribute(){ // get nama gudang
        return $this->retur_penjualan->gudang->nama_gudang;
    }

    public function getNoAccAttribute(){ // get no account toko
        return $this->retur_penjualan->toko->no_acc;
    }

    public function getCustNoAttribute(){ // get cust no
        return $this->retur_penjualan->toko->cust_no;
    }

    public function getNamaTokoAttribute(){ // get nama toko
        return $this->retur_penjualan->toko->nama_toko;
    }

    public function getAlamatAttribute(){ // alamat toko
        return $this->retur_penjualan->toko->alamat;
    }

    public function getTimAttribute(){ // nama tim
        return $this->retur_penjualan->toko->ketentuan_toko->tim->nama_tim;
    }

    public function getNpwpAttribute(){ // npwp toko
        return $this->retur_penjualan->toko->ketentuan_toko->npwp;
    }

    public function getPotonganAttribute(){ // harga dbp
        $potongan = $this->retur_penjualan->potongan;
        return $this->npwp != '' && $this->npwp != null  ? $potongan/1.1 : $potongan;
    }

    public function getDbpAttribute(){ // harga dbp
        return $this->barang->dbp;
    }

    public function getQtyInCartonAttribute(){ // jumlah retur dalam carton
        return $this->qty_dus + ($this->qty_pcs / $this->barang->isi);
    }

    public function getSubtotalAttribute(){
        return $this->harga * $this->qty_in_carton;
    }

    public function getSubtotalAfterTaxAttribute() { // subtotal sesudah ppn
        return $this->harga * $this->qty_in_carton * 1.1;
    }

    public function getPpnAttribute(){ // ppn
        return $this->dpp * 0.1;
    }

    public function getPeriodeAttribute(){ // periode
        $sales_retur_date = $this->retur_penjualan->sales_retur_date;

        $periode = \Carbon\Carbon::createFromFormat('Y-m-d', $sales_retur_date)->month;
        return $periode;
    }

    public function getDiscountAttribute(){ // get diskon sebelum ppn
        $disc_rupiah = $this->disc_nominal / 1.1 * $this->qty_in_carton;
        $disc_persen = $this->disc_persen / 100 * $this->subtotal;
        $potongan    = ($this->subtotal - $disc_persen - $disc_rupiah) * $this->potongan / 100;
        $discount    = $disc_rupiah + $disc_persen + $potongan;
        return $discount;
    }

    public function getDiscountAfterTaxAttribute(){ // get diskon setelah ppn
        $disc_rupiah = $this->disc_nominal * $this->qty_in_carton;
        $disc_persen = $this->disc_persen / 100 * $this->subtotal_after_tax;
        $potongan    = ($this->subtotal_after_tax - $disc_persen - $disc_rupiah) * $this->potongan / 100;
        $discount    = $disc_rupiah + $disc_persen + $potongan;
        return $discount;
    }

    public function getDppAttribute(){
        return $this->subtotal - $this->discount;
    }

    public function getNetAttribute() {
        return $this->subtotal_after_tax - $this->discount_after_tax;
    }
}

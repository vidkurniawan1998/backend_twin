<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class DetailPenerimaanBarang extends Model
{
    // use SoftDeletes;

    protected $table = 'detail_penerimaan_barang';

    protected $fillable = [
        'id_penerimaan_barang',
        'id_barang',
        'id_harga',
        'qty',
        'qty_pcs',

        // 'price', // numberFormat($price,2)  double(11,2)
        // 'disc_p', // double(5,2)
        // 'disc_p_nom' => 0, // double(11,2)
        // 'disc_n' => 0, // double(11,2)
        // 'total_disc' => 0, // double(11,2)
        // 'subtotal' => 0, // double(11,2)
        // 'ppn' => 0, // double(11,2)
        // 'grand_total' => 0, // double(11,2)

        'keterangan',
        'created_by',
        'updated_by',
        // 'deleted_by'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        // 'deleted_at'
    ];

    protected $casts = [
        'id_barang' => 'integer',
        'id_harga'  => 'integer',
        'qty'       => 'integer',
        'qty_pcs'   => 'integer',
    ];

    public function penerimaan_barang(){
        return $this->belongsTo('App\Models\PenerimaanBarang', 'id_penerimaan_barang')->withTrashed();
    }

    public function harga_barang(){
        return $this->belongsTo('App\Models\HargaBarang', 'id_harga');
    }

    public function barang(){
        return $this->belongsTo('App\Models\Barang', 'id_barang')->withTrashed();
    }

    public function getIsApprovedAttribute(){
        return $this->penerimaan_barang->is_approved;
    }

    public function getTglBongkarAttribute(){
        return $this->penerimaan_barang->tgl_bongkar;
    }

    public function getKodeBarangAttribute()
    {
        return $this->barang->kode_barang;
    }

    public function getNamaBarangAttribute()
    {
        return $this->barang->nama_barang;
    }

    public function getPriceAfterTaxAttribute(){
        return optional($this->harga_barang)->harga;
    }

    public function getPriceBeforeTaxAttribute(){
        return optional($this->harga_barang)->harga / 1.1;
    }

    public function getSumCartonAttribute(){
        $sum_carton = $this->qty + ($this->qty_pcs / $this->barang->isi);
        return $sum_carton;
    }

    public function getPpnAttribute(){
        return (optional($this->harga_barang)->harga / 11) * $this->sum_carton;
    }

    public function getSubtotalAttribute(){
        return $this->price_before_tax * $this->sum_carton;
    }

    public function getGrandTotalAttribute(){
        return $this->price_after_tax * $this->sum_carton;
    }



}

<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViewDetailPenjualan extends Model
{
    protected $table = 'v_detail_penjualan';

    public function getSubTotalAttribute()
    {
        return $this->price_before_tax * $this->ctn;
    }

    public function getPriceBeforeTaxAttribute(){
        return $this->harga / 1.1;
    }

    public function getDiscountAttribute(){
        $disc_rupiah = ($this->disc_rupiah / 1.1) * $this->ctn;
        $disc_persen = ($this->disc_persen / 100) * $this->subtotal;
        $discount = $disc_rupiah + $disc_persen;
        return $discount;
    }

    public function getPpnAttribute(){
        $ppn = ($this->subtotal - $this->discount) / 10;
        return floatval($ppn);
    }
}
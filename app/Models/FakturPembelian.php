<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FakturPembelian extends Model
{
    use SoftDeletes;
    protected $table    = 'faktur_pembelian';
    protected $fillable = [
        'no_invoice',
        'faktur_pajak',
        'tanggal',
        'tanggal_invoice',
        'tanggal_jatuh_tempo',
        'tanggal_bayar',
        'disc_persen',
        'disc_value',
        'status',
        'id_principal',
        'id_depo',
        'id_perusahaan',
        'id_user',
        'ppn'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'disc_persen'   => 'float',
        'disc_value'    => 'float',
        'ppn'           => 'int',
        'ppn_value'     => 'float',
        'dpp'           => 'float',
        'grand_total'   => 'float'
    ];

    public function principal()
    {
        return $this->belongsTo('App\Models\Principal', 'id_principal', 'id');
    }

    public function depo()
    {
        return $this->belongsTo('App\Models\Depo', 'id_depo', 'id');
    }

    public function perusahaan()
    {
        return $this->belongsTo('App\Models\Perusahaan', 'id_perusahaan', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'id_user', 'id');
    }

    public function penerimaan_barang()
    {
        return $this->belongsToMany('App\Models\PenerimaanBarang', 'faktur_pembelian_penerimaan', 'id_faktur_pembelian', 'id_penerimaan_barang');
    }

    public function detail_faktur_pembelian()
    {
        return $this->hasMany('App\Models\DetailFakturPembelian', 'id_faktur_pembelian', 'id');
    }

    public function detail_pelunasan_pembelian()
    {
        return $this->hasMany('App\Models\DetailPelunasanPembelian', 'id_faktur_pembelian', 'id')->where('status','=','approved');
    }

    public function getSubtotalAttribute()
    {
        return $this->detail_faktur_pembelian->sum('dpp');
    }

    public function getDiscountAttribute()
    {
        return $this->subtotal * $this->disc_persen / 100 + $this->disc_value;
    }

    public function getDppAttribute()
    {
        return $this->subtotal - $this->discount;
    }

    public function getPpnValueAttribute()
    {
        return $this->dpp * $this->ppn / 100;
    }

    public function getGrandTotalAttribute()
    {
        return $this->dpp + $this->ppn_value;
    }

    public function getOverDueAttribute()
    {
        if ($this->status == 'paid') {
            return '';
        }

        $jt     = \Carbon\Carbon::parse($this->tanggal_jatuh_tempo);
        $today  = \Carbon\Carbon::today();
        $od     = $jt->diffInDays($today, false);
        return $od;
    }

    public function getJumlahPembayaranAttribute()
    {
        return $this->detail_pembayaran->sum('nominal');

    }
}

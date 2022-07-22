<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;

class InvoiceNote extends Model
{ 
    use SoftDeletes;
    protected $table = 'invoice_note';

    protected $fillable = [
        'no_invoice',
        'id_penjualan',
        'tanggal',
        'keterangan',
        'keterangan_reschedule',
        'status',
        'deleted_by',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

     public function penjualan()
    {
        return $this->belongsTo('App\Models\Penjualan', 'id_penjualan', 'id');
    }

    public function riwayat_invoice_note()
    {
        return $this->hasMany('App\Models\RiwayatInvoiceNote', 'id_invoice_note', 'id')->orderBy('id','DESC');
    }

}

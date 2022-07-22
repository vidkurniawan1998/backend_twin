<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;

class RiwayatInvoiceNote extends Model
{
    use SoftDeletes;
    protected $table = 'riwayat_invoice_note';

    protected $fillable = [
        'id_invoice_note',
        'no_invoice',
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

}

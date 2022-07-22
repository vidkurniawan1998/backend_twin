<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class TargetSalesman extends Model
{
    protected $table = 'target_salesman';
    protected $fillable = [
        'id_perusahaan',
        'id_depo',
        'id_user',
        'mulai_tanggal',
        'sampai_tanggal',
        'hari_kerja',
        'target',
        'input_by'
    ];
    protected $casts = [
        'id_perusahaan' => 'integer',
        'id_depo'       => 'integer',
        'id_user'       => 'integer',
        'target'        => 'float',
        'hari_kerja'    => 'integer'
    ];

    public function perusahaan()
    {
        return $this->belongsTo('App\Models\Perusahaan', 'id_perusahaan', 'id');
    }

    public function depo()
    {
        return $this->belongsTo('App\Models\Depo', 'id_depo', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'id_user', 'id');
    }

    public function salesman()
    {
        return $this->belongsTo('App\Models\Salesman', 'id_user', 'user_id');
    }
}

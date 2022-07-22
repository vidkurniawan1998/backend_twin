<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class SharingPromo extends Model
{
    protected $table = 'sharing_promo';
    protected $fillable = [
        'id_promo',
        'persen_principal',
        'persen_dist',
        'nominal_principal',
        'nominal_dist',
        'extra_principal',
        'extra_dist'
    ];

    protected $casts = [
        'persen_principal' => 'integer',
        'persen_dist' => 'integer',
        'nominal_principal' => 'integer',
        'nominal_dist' => 'integer',
        'extra_principal' => 'integer',
        'extra_dist' => 'integer'
    ];

    public function promo()
    {
        return $this->belongsTo('App\Models\Promo', 'id_promo', 'id')->withTrashed();
    }
}

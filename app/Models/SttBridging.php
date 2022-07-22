<?php

namespace App\Models;

use App\Traits\DepoLocationTrait;
use Illuminate\Database\Eloquent\Model;

class SttBridging extends Model
{
    use DepoLocationTrait;
    protected $table = 'stt_bridging';
    protected $guarded = [];
    protected $appends = ['depo'];

    public function getDepoAttribute()
    {
        return $this->depoLocation($this->gudang);
    }
}

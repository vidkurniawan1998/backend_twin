<?php


namespace App\Traits;

use App\Models\Depo;

trait HasDepoTrait {
    public function depo() {
        return $this->belongsToMany(Depo::class,'user_depo');
    }
}
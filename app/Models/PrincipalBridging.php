<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrincipalBridging extends Model
{
    use SoftDeletes;

    protected $table    = "principal_bridging";
    protected $guarded  = [];
}

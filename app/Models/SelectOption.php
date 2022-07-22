<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SelectOption extends Model
{
    protected $table = 'select_options';
    protected $fillable = ['code', 'value', 'text'];

    public function getValueAttribute($value)
    {
        return strtolower($value);
    }
}

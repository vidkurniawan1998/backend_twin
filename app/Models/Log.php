<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $fillable = ['action', 'description', 'user_id', 'created_at', 'updated_at'];

    public function user() {
        return $this->belongsTo('\App\Models\User', 'user_id');
    }
}
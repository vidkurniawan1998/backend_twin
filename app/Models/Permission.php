<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    public function roles()
    {
        return $this->belongsToMany('App\Models\Role', 'roles_permissions');
    }

    public function users() {
        return $this->belongsToMany('App\Models\User', 'users_permissions');
    }
}
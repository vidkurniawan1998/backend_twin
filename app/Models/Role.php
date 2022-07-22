<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public function permissions()
    {
        return $this->belongsToMany('App\Models\Permission', 'roles_permissions');
    }

    public function users() {
        return $this->belongsToMany('App\Models\User', 'users_roles');
    }
}


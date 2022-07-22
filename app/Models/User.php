<?php

namespace App\Models;

use App\Traits\HasPermissionsTrait;
use App\Traits\HasDepoTrait;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Model implements JWTSubject, AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, SoftDeletes, HasPermissionsTrait, HasDepoTrait;
    const STATUS_ACTIVE             = 'active';
    const STATUS_NON_ACTIVE         = 'non_active';
    const STATUS_NEED_ACTIVATION    = 'need_activation';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'nik',
        'avatar',
        'status',
        'role',
        // 'created_by',
        // 'updated_by',
        // 'deleted_by'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function salesman()
    {
        return $this->hasOne('App\Models\Salesman', 'user_id');
    }

    public function kepala_gudang()
    {
        return $this->hasOne('App\Models\KepalaGudang', 'user_id');
    }

    public function gudang() {
        return $this->belongsToMany('App\Models\Gudang', 'user_gudang');
    }

    public function perusahaan()
    {
        return $this->belongsToMany('App\Models\Perusahaan', 'user_perusahaan');
    }

    public function depo()
    {
        return $this->belongsToMany('App\Models\Depo', 'user_depo');
    }

    public function principal()
    {
        return $this->belongsToMany('App\Models\Principal', 'user_principal', 'user_id', 'principal_id');
    }

}

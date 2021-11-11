<?php

namespace App;

use App\Models\Order;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;
    public $table = 'users';

    const STATUS_DISABLE    = 0;
    const STATUS_ENABLE     = 1;

    const STATUS_MAP = [
        self::STATUS_ENABLE     => 'Enable',
        self::STATUS_DISABLE     => 'Disable',
    ];

    const LEVEL_USER    = 1;

    const LEVEL_ADMIN   = 2;

    const LEVEL_MAP = [
        self::LEVEL_USER    => 'User',
        self::LEVEL_ADMIN   => 'Admin',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'level',
        'status',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getLevelNameAttribute($value)
    {
        return array_key_exists($this->level, self::LEVEL_MAP) ? self::LEVEL_MAP[$this->level] : '';
    }

    public function getStatusNameAttribute($value)
    {
        return array_key_exists($this->status, self::STATUS_MAP) ? self::STATUS_MAP[$this->status] : "";
    }

}

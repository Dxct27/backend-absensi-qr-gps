<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasUlids, HasApiTokens, HasProfilePhoto, HasFactory, Notifiable;

    protected $fillable = [
        'nip',
        'name',
        'email',
        'password',
        'google_id', 
        'avatar',    
        'group',
        'opd_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public static $groups = ['user', 'admin', 'superadmin'];

    public function getIsUserAttribute(): bool
    {
        return $this->group === 'user';
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->group === 'admin' || $this->isSuperadmin;
    }

    public function getIsSuperadminAttribute(): bool
    {
        return $this->group === 'superadmin';
    }

    public function getIsNotAdminAttribute(): bool
    {
        return !$this->isAdmin;
    }

    public function opd() 
    {
        return $this->belongsTo(Opd::class);
    }

    public function attendanced()
    {
        return $this->hasMany(Attendance::class);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}

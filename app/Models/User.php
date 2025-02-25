<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasUlids;
    use HasApiTokens;
    use HasProfilePhoto;    
    use HasFactory; 
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nip',
        'name',
        'email',
        'password',
        'group',
        'opd_id',
        'profile_photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public static $groups = ['user', 'admin', 'superadmin'];

    final public function getIsUserAttribute(): bool
    {
        return $this->group === 'user';
    }

    final public function getIsAdminAttribute(): bool
    {
        return $this->group === 'admin' || $this->isSuperadmin;
    }

    final public function getIsSuperadminAttribute(): bool
    {
        return $this->group === 'superadmin';
    }

    final public function getIsNotAdminAttribute(): bool
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

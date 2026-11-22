<?php

namespace App\Models;

use App\Traits\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, Auditable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'first_name',
        'last_name',
        'phone',
        'photo_url',
        'firebase_uid',
        'public_id',
        'status',
        'created_by_admin',
        'kyc_tier',
        'kyc_status',
        'kyc_verified_at',
        'currency_preference',
        'must_change_password_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'kyc_verified_at' => 'datetime',
            'must_change_password_at' => 'datetime',
            'password' => 'hashed',
            'created_by_admin' => 'boolean',
        ];
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('superadmin');
    }

    public function hasKycTier(int $tier): bool
    {
        return $this->kyc_tier >= $tier;
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agency extends Model
{
    protected $fillable = [
        'name', 'slug', 'logo_url', 'description', 'city_id', 'owner_user_id',
        'email', 'phone', 'website', 'status', 'rejection_reason',
        'is_active', 'kyc_verified'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'kyc_verified' => 'boolean',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function busTrips(): HasMany
    {
        return $this->hasMany(\App\Modules\Bus\Models\BusTrip::class);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'approved' && (bool) ($this->attributes['is_active'] ?? true);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $fillable = ['name_en', 'name_fr', 'region', 'country'];

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'fr' ? $this->name_fr : $this->name_en;
    }

    public function agencies(): HasMany
    {
        return $this->hasMany(Agency::class);
    }
}
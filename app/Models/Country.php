<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = ['code', 'name', 'currency_code', 'phone_code', 'is_enabled'];

    protected $casts = ['is_enabled' => 'boolean'];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'country_code', 'code');
    }
}
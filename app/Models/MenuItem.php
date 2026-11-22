<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $fillable = [
        'menu_id', 'parent_id', 'title_en', 'title_fr', 'url', 'route_name',
        'icon', 'sort_order', 'is_active', 'open_in_new_tab'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'open_in_new_tab' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->where('is_active', true)->orderBy('sort_order');
    }

    public function getTitleAttribute(): string
    {
        return app()->getLocale() === 'fr' ? $this->title_fr : $this->title_en;
    }

    public function getUrlAttribute(): ?string
    {
        if ($this->route_name) {
            return route($this->route_name);
        }
        return $this->attributes['url'] ?? null;
    }

    public function getTargetAttribute(): string
    {
        return $this->open_in_new_tab ? '_blank' : '_self';
    }
}
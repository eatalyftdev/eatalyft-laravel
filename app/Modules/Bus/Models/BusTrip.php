<?php

namespace App\Modules\Bus\Models;

use App\Models\Agency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusTrip extends Model
{
    protected $table = 'bus_trips';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'departure_at' => 'immutable_datetime',
            'arrival_at' => 'immutable_datetime',
            'allowed_channels' => 'array',
        ];
    }

    public function agency(): BelongsTo { return $this->belongsTo(Agency::class); }
    public function seats(): HasMany { return $this->hasMany(BusTripSeat::class, 'bus_trip_id'); }
    public function route(): BelongsTo { return $this->belongsTo(BusRoute::class, 'bus_route_id'); }
}

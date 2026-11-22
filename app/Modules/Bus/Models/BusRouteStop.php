<?php

namespace App\Modules\Bus\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\City;

class BusRouteStop extends Model
{
    protected $table = 'bus_route_stops';
    protected $guarded = ['id'];
    public function route(): BelongsTo { return $this->belongsTo(BusRoute::class, 'bus_route_id'); }
    public function city(): BelongsTo { return $this->belongsTo(City::class); }
}

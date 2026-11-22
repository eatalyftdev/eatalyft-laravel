<?php

namespace App\Modules\Bus\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusRoute extends Model
{
    protected $table = 'bus_routes';
    protected $guarded = ['id'];
    public function stops(): HasMany { return $this->hasMany(BusRouteStop::class)->orderBy('stop_order'); }
}

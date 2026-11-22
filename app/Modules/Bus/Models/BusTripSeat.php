<?php

namespace App\Modules\Bus\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusTripSeat extends Model
{
    protected $table = 'bus_trip_seats';
    protected $guarded = ['id'];
    protected function casts(): array { return ['hold_expires_at' => 'immutable_datetime']; }
    public function trip(): BelongsTo { return $this->belongsTo(BusTrip::class, 'bus_trip_id'); }
    public function booking(): BelongsTo { return $this->belongsTo(BusBooking::class, 'booking_id'); }
}

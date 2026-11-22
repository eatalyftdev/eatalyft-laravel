<?php

namespace App\Modules\Bus\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusTicket extends Model
{
    protected $table = 'bus_tickets';
    protected $guarded = ['id'];
    protected function casts(): array { return ['sold_at' => 'immutable_datetime', 'scanned_at' => 'immutable_datetime']; }
    public function booking(): BelongsTo { return $this->belongsTo(BusBooking::class, 'booking_id'); }
    public function trip(): BelongsTo { return $this->belongsTo(BusTrip::class, 'bus_trip_id'); }
    public function events(): HasMany { return $this->hasMany(BusTicketEvent::class, 'bus_ticket_id'); }
}

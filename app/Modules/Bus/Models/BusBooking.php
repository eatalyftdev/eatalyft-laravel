<?php

namespace App\Modules\Bus\Models;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusBooking extends Model
{
    protected $table = 'bus_bookings';
    protected $guarded = ['id'];
    protected function casts(): array { return ['hold_expires_at' => 'immutable_datetime', 'metadata' => 'array']; }
    public function agency(): BelongsTo { return $this->belongsTo(Agency::class); }
    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_id'); }
    public function tickets(): HasMany { return $this->hasMany(BusTicket::class, 'booking_id'); }
}

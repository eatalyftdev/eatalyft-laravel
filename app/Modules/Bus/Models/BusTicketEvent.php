<?php

namespace App\Modules\Bus\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusTicketEvent extends Model
{
    protected $table = 'bus_ticket_events';
    protected $guarded = ['id'];
    protected function casts(): array { return ['payload' => 'array']; }
    public function ticket(): BelongsTo { return $this->belongsTo(BusTicket::class, 'bus_ticket_id'); }
}

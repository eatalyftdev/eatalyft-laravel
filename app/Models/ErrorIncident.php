<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ErrorIncident extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'fingerprint',
        'severity',
        'exception_class',
        'message',
        'file',
        'line',
        'stack_trace',
        'environment',
        'request_method',
        'request_url',
        'route_name',
        'http_status',
        'request_id',
        'user_id',
        'occurrence_count',
        'first_seen_at',
        'last_seen_at',
        'status',
        'resolved_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'uuid' => 'string',
        'occurrence_count' => 'integer',
        'http_status' => 'integer',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'stack_trace',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (ErrorIncident $incident): void {
            if (empty($incident->uuid)) {
                $incident->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the user associated with the incident.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine if the incident is resolved.
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    /**
     * Determine if the incident is open.
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Determine if the incident is critical.
     */
    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    /**
     * Increment the occurrence count and update last_seen_at.
     */
    public function recordOccurrence(): void
    {
        $this->increment('occurrence_count');
        $this->update(['last_seen_at' => now()]);
    }

    /**
     * Mark the incident as resolved.
     */
    public function markResolved(): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    /**
     * Mark the incident as investigating.
     */
    public function markInvestigating(): void
    {
        $this->update(['status' => 'investigating']);
    }

    /**
     * Reopen a resolved incident.
     */
    public function reopen(): void
    {
        $this->update([
            'status' => 'open',
            'resolved_at' => null,
        ]);
    }

    /**
     * Get the short fingerprint for display.
     */
    public function getShortFingerprintAttribute(): string
    {
        return substr($this->fingerprint, 0, 12);
    }
}

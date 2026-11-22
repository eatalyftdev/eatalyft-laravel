<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'platform',
        'fcm_token',
        'device_model',
        'app_version',
        'os_version',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function registerToken(int $userId, string $platform, string $fcmToken, ?array $meta = null): self
    {
        return self::updateOrCreate(
            ['fcm_token' => $fcmToken],
            [
                'user_id' => $userId,
                'platform' => $platform,
                'device_model' => $meta['device_model'] ?? null,
                'app_version' => $meta['app_version'] ?? null,
                'os_version' => $meta['os_version'] ?? null,
                'last_seen_at' => now(),
            ]
        );
    }

    public function markSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }
}
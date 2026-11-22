<?php

namespace App\Console\Commands;

use App\Modules\Bus\Models\BusBooking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireBusSeatHolds extends Command
{
    protected $signature = 'bus:expire-seat-holds';
    protected $description = 'Release expired bus seat holds and mark their bookings expired';

    public function handle(): int
    {
        $expiredCount = 0;

        BusBooking::query()
            ->where('status', 'held')
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($bookings) use (&$expiredCount) {
                foreach ($bookings as $candidate) {
                    DB::transaction(function () use ($candidate, &$expiredCount) {
                        $booking = BusBooking::query()->lockForUpdate()->find($candidate->id);
                        if (! $booking || $booking->status !== 'held' || ! $booking->hold_expires_at || $booking->hold_expires_at->isFuture()) {
                            return;
                        }

                        DB::table('bus_trip_seats')
                            ->where('booking_id', $booking->id)
                            ->where('status', 'held')
                            ->update([
                                'status' => 'available',
                                'booking_id' => null,
                                'hold_expires_at' => null,
                                'updated_at' => now(),
                            ]);

                        $booking->forceFill(['status' => 'expired'])->save();
                        $expiredCount++;
                    }, 3);
                }
            });

        $this->info("Expired {$expiredCount} bus booking hold(s).");
        return self::SUCCESS;
    }
}

<?php

namespace App\Modules\Bus\Services;

use App\Modules\Bus\Models\BusBooking;
use App\Modules\Bus\Models\BusTrip;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BusBookingService
{
    /** Create a short-lived, atomic seat hold. This does not collect or confirm payment. */
    public function holdSeats(array $data, ?int $customerId): BusBooking
    {
        return DB::transaction(function () use ($data, $customerId) {
            if (! empty($data['idempotency_key'])) {
                $existing = BusBooking::query()->where('idempotency_key', $data['idempotency_key'])->first();
                if ($existing) {
                    if ($existing->customer_id !== $customerId) {
                        throw ValidationException::withMessages(['idempotency_key' => 'This request key has already been used.']);
                    }
                    return $existing;
                }
            }

            $trip = BusTrip::query()->lockForUpdate()->find($data['trip_id']);
            if (! $trip || ! in_array($trip->status, ['scheduled', 'boarding'], true)) {
                throw ValidationException::withMessages(['trip_id' => 'This trip is not available for booking.']);
            }

            $cutoff = $trip->departure_at->copy()->subMinutes($trip->sales_cutoff_minutes);
            if (now()->greaterThanOrEqualTo($cutoff)) {
                throw ValidationException::withMessages(['trip_id' => 'Booking has closed for this departure.']);
            }

            $labels = array_values(array_unique($data['seat_labels']));
            if (count($labels) !== count($data['seat_labels'])) {
                throw ValidationException::withMessages(['seat_labels' => 'Duplicate seat selections are not allowed.']);
            }

            $seats = $trip->seats()->whereIn('seat_label', $labels)->orderBy('id')->lockForUpdate()->get();
            if ($seats->count() !== count($labels)) {
                throw ValidationException::withMessages(['seat_labels' => 'One or more selected seats do not exist.']);
            }

            foreach ($seats as $seat) {
                $expiredHold = $seat->status === 'held' && $seat->hold_expires_at && $seat->hold_expires_at->isPast();
                if ($expiredHold) {
                    $seat->status = 'available';
                    $seat->booking_id = null;
                    $seat->hold_expires_at = null;
                    $seat->save();
                }
                if ($seat->status !== 'available') {
                    throw ValidationException::withMessages(['seat_labels' => "Seat {$seat->seat_label} is no longer available."]);
                }
            }

            $totalMinor = (int) $seats->sum('price_minor');
            $expiresAt = now()->addMinutes(10);
            $booking = BusBooking::query()->create([
                'booking_ref' => 'ELB-'.Str::upper(Str::random(12)),
                'customer_id' => $customerId,
                'channel' => $data['channel'] ?? 'web',
                'agency_id' => $trip->agency_id,
                'branch_id' => $data['branch_id'] ?? null,
                'sold_by_user_id' => $data['sold_by_user_id'] ?? null,
                'contact_name' => $data['contact_name'],
                'contact_phone' => $data['contact_phone'],
                'contact_email' => $data['contact_email'] ?? null,
                'status' => 'held',
                'hold_expires_at' => $expiresAt,
                'total_minor' => $totalMinor,
                'currency' => 'XAF',
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'metadata' => ['trip_id' => $trip->id, 'seat_labels' => $labels],
            ]);

            foreach ($seats as $seat) {
                $seat->forceFill([
                    'status' => 'held',
                    'booking_id' => $booking->id,
                    'hold_expires_at' => $expiresAt,
                ])->save();
            }

            return $booking->load('tickets');
        }, 3);
    }
}

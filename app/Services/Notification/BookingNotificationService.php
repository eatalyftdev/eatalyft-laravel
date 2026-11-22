<?php

namespace App\Services\Notification;

use App\Modules\Bus\Models\BusBooking;
use App\Modules\Bus\Models\BusTicket;
use Illuminate\Support\Facades\Log;

/**
 * BookingNotificationService - Handles notification dispatch for booking events.
 *
 * Sends notifications when:
 * - Booking is created (seat hold)
 * * Booking is confirmed (payment)
 * - Ticket is issued
 * - Booking is cancelled
 * - Seat hold expires
 */
class BookingNotificationService
{
    /**
     * Notify customer of booking hold creation.
     */
    public function bookingCreated(BusBooking $booking): void
    {
        \Log::info('Booking created notification', [
            'booking_id' => $booking->id,
            'booking_ref' => $booking->booking_ref,
            'customer_id' => $booking->customer_id,
        ]);

        // Notification would be dispatched here via Notification facade
        // when notification system is fully configured
    }

    /**
     * Notify customer of booking confirmation (payment).
     */
    public function bookingConfirmed(BusBooking $booking): void
    {
        \Log::info('Booking confirmed notification', [
            'booking_id' => $booking->id,
            'booking_ref' => $booking->booking_ref,
            'customer_id' => $booking->customer_id,
        ]);
    }

    /**
     * Notify customer of ticket issuance.
     */
    public function ticketIssued(BusTicket $ticket): void
    {
        \Log::info('Ticket issued notification', [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'booking_id' => $ticket->booking_id,
        ]);
    }

    /**
     * Notify customer of booking cancellation.
     */
    public function bookingCancelled(BusBooking $booking): void
    {
        \Log::info('Booking cancelled notification', [
            'booking_id' => $booking->id,
            'booking_ref' => $booking->booking_ref,
            'customer_id' => $booking->customer_id,
        ]);
    }

    /**
     * Notify customer of seat hold expiration.
     */
    public function seatHoldExpired(BusBooking $booking): void
    {
        \Log::info('Seat hold expired notification', [
            'booking_id' => $booking->id,
            'booking_ref' => $booking->booking_ref,
            'customer_id' => $booking->customer_id,
        ]);
    }

    /**
     * Notify customer of trip change.
     */
    public function tripChanged(BusBooking $booking, array $changes): void
    {
        \Log::info('Trip change notification', [
            'booking_id' => $booking->id,
            'booking_ref' => $booking->booking_ref,
            'customer_id' => $booking->customer_id,
            'changes' => $changes,
        ]);
    }
}

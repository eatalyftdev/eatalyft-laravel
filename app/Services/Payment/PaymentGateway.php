<?php

namespace App\Services\Payment;

use App\Modules\Bus\Models\BusBooking;

/**
 * PaymentGateway interface for payment processing.
 *
 * This interface defines the contract for payment gateways that can be
 * integrated with the EataLyft booking system. No live payment provider
 * is integrated in this phase. Implementations must be added when a
 * payment provider is approved and configured.
 *
 * IMPORTANT: No live payment processing is implemented in this phase.
 * All implementations must return a "not implemented" response until
 * a dedicated payment phase is approved.
 */
interface PaymentGateway
{
    /**
     * Create a payment intent for a booking.
     *
     * @param  BusBooking  $booking
     * @param  array  $data Additional payment data
     * @return array Payment intent data with 'payment_intent_id', 'client_secret', 'amount', 'currency', 'status'
     * @throws \Exception If the payment gateway is not configured
     */
    public function createPaymentIntent(BusBooking $booking, array $data = []): array;

    /**
     * Confirm a payment intent after user authorization.
     *
     * @param  string  $paymentIntentId
     * @param  array  $data Additional confirmation data
     * @return array Payment confirmation result with 'status', 'payment_intent_id', 'receipt_url'
     * @throws \Exception If the payment cannot be confirmed
     */
    public function confirmPaymentIntent(string $paymentIntentId, array $data = []): array;

    /**
     * Cancel a payment intent.
     *
     * @param  string  $paymentIntentId
     * @return array Cancellation result
     * @throws \Exception If the payment cannot be cancelled
     */
    public function cancelPaymentIntent(string $paymentIntentId): array;

    /**
     * Retrieve payment intent status.
     *
     * @param  string  $paymentIntentId
     * @return array Payment intent status
     */
    public function getPaymentIntentStatus(string $paymentIntentId): array;

    /**
     * Check if the payment gateway is configured and available.
     *
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * Get the gateway name for display purposes.
     *
     * @return string
     */
    public function getGatewayName(): string;
}

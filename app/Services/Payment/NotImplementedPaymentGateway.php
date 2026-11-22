<?php

namespace App\Services\Payment;

/**
 * NotImplementedPaymentGateway - Placeholder payment gateway.
 *
 * This implementation returns "not implemented" for all payment operations.
 * It is used when no payment provider has been configured. No live payment
 * processing is performed. All methods return a "payment not available"
 * response to prevent any accidental payment processing.
 */
class NotImplementedPaymentGateway implements PaymentGateway
{
    /**
     * Create a payment intent.
     *
     * @return array Payment intent with 'status' => 'not_implemented'
     */
    public function createPaymentIntent(\App\Modules\Bus\Models\BusBooking $booking, array $data = []): array
    {
        return [
            'status' => 'not_implemented',
            'message' => 'Payment processing is not available. Payment integration is deferred to a future phase.',
            'payment_intent_id' => null,
            'client_secret' => null,
            'amount' => $booking->total_minor,
            'currency' => $booking->currency,
        ];
    }

    /**
     * Confirm a payment intent.
     *
     * @return array Confirmation with 'status' => 'not_implemented'
     */
    public function confirmPaymentIntent(string $paymentIntentId, array $data = []): array
    {
        return [
            'status' => 'not_implemented',
            'message' => 'Payment confirmation is not available.',
            'payment_intent_id' => $paymentIntentId,
        ];
    }

    /**
     * Cancel a payment intent.
     *
     * @return array Cancellation with 'status' => 'not_implemented'
     */
    public function cancelPaymentIntent(string $paymentIntentId): array
    {
        return [
            'status' => 'not_implemented',
            'message' => 'Payment cancellation is not available.',
            'payment_intent_id' => $paymentIntentId,
        ];
    }

    /**
     * Get payment intent status.
     *
     * @return array Status with 'status' => 'not_implemented'
     */
    public function getPaymentIntentStatus(string $paymentIntentId): array
    {
        return [
            'status' => 'not_implemented',
            'payment_intent_id' => $paymentIntentId,
        ];
    }

    /**
     * Check if the payment gateway is configured.
     *
     * @return bool Always false - no payment provider is configured
     */
    public function isAvailable(): bool
    {
        return false;
    }

    /**
     * Get the gateway name.
     *
     * @return string
     */
    public function getGatewayName(): string
    {
        return 'Not Implemented';
    }
}

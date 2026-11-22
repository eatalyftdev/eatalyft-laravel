<?php

namespace App\Services\Payment;

use App\Modules\Bus\Models\BusBooking;
use Illuminate\Support\Facades\Log;

/**
 * PaymentService - Service layer for payment operations.
 *
 * This service delegates to a configured PaymentGateway implementation.
 * No live payment processing is performed in this phase.
 */
class PaymentService
{
    /**
     * @var PaymentGateway
     */
    protected $gateway;

    /**
     * Create a new payment service instance.
     */
    public function __construct(PaymentGateway $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Create a payment intent for a booking.
     *
     * @param  BusBooking  $booking
     * @param  array  $data
     * @return array
     */
    public function createPaymentIntent(BusBooking $booking, array $data = []): array
    {
        return $this->gateway->createPaymentIntent($booking, $data);
    }

    /**
     * Confirm a payment intent.
     *
     * @param  string  $paymentIntentId
     * @param  array  $data
     * @return array
     */
    public function confirmPaymentIntent(string $paymentIntentId, array $data = []): array
    {
        return $this->gateway->confirmPaymentIntent($paymentIntentId, $data);
    }

    /**
     * Cancel a payment intent.
     *
     * @param  string  $paymentIntentId
     * @return array
     */
    public function cancelPaymentIntent(string $paymentIntentId): array
    {
        return $this->gateway->cancelPaymentIntent($paymentIntentId);
    }

    /**
     * Get payment intent status.
     *
     * @param  string  $paymentIntentId
     * @return array
     */
    public function getPaymentIntentStatus(string $paymentIntentId): array
    {
        return $this->gateway->getPaymentIntentStatus($paymentIntentId);
    }

    /**
     * Check if payment processing is available.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->gateway->isAvailable();
    }

    /**
     * Get the gateway name.
     *
     * @return string
     */
    public function getGatewayName(): string
    {
        return $this->gateway->getGatewayName();
    }

    /**
     * Check if a booking can proceed to payment confirmation.
     *
     * @param  BusBooking  $booking
     * @return array
     */
    public function canConfirmPayment(BusBooking $booking): array
    {
        if (!$this->isAvailable()) {
            return [
                'allowed' => false,
                'reason' => 'Payment processing is not configured.',
            ];
        }

        if ($booking->status !== 'held') {
            return [
                'allowed' => false,
                'reason' => 'Booking must be in held status for payment confirmation.',
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
        ];
    }
}

<?php
namespace App\Services\Otp;

class LogSender implements OtpSender
{
    public function send(string $destination, string $code, string $purpose): void
    {
        try {
            \Log::channel('otp')->info("OTP [$purpose] to $destination: $code");
        } catch (\Throwable $e) {
        }
    }
}
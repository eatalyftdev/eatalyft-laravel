<?php
namespace App\Services\Otp;

class SmsSender implements OtpSender
{
    public function send(string $destination, string $code, string $purpose): void
    {
    }
}
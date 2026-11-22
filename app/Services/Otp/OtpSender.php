<?php
namespace App\Services\Otp;

interface OtpSender
{
    public function send(string $destination, string $code, string $purpose): void;
}
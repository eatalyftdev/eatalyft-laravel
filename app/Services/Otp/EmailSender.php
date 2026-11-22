<?php
namespace App\Services\Otp;

class EmailSender implements OtpSender
{
    public function send(string $destination, string $code, string $purpose): void
    {
        \Mail::raw("Your code is $code", fn($m)=>$m->to($destination)->subject('Verification code'));
    }
}
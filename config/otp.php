<?php
return [
    'digits'=>env('OTP_DIGITS',6),
    'expiry_minutes'=>env('OTP_EXPIRY_MINUTES',5),
    'max_attempts'=>env('OTP_MAX_ATTEMPTS',5),
    'resend_cooldown'=>env('OTP_RESEND_COOLDOWN',60),
    'per_destination_limit'=>env('OTP_PER_DEST_LIMIT',3),
    'per_ip_limit'=>env('OTP_PER_IP_LIMIT',10),
    'whatsapp_driver'=>env('OTP_WHATSAPP_DRIVER','log'),
    'email_driver'=>env('OTP_EMAIL_DRIVER','log'),
    'sms_driver'=>env('OTP_SMS_DRIVER','log'),
    'show_code_on_screen'=>env('OTP_SHOW_CODE_ON_SCREEN', env('APP_ENV')==='local' || env('APP_ENV')==='testing') && env('APP_DEBUG',true),
];

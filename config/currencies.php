<?php

return [
    'default' => env('DEFAULT_CURRENCY', 'XAF'),
    'supported' => ['XAF', 'USD', 'EUR', 'GBP', 'CAD', 'NGN', 'KES', 'GHS', 'ZAR'],
    'symbols' => [
        'XAF' => 'FCFA',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'CAD' => 'CA$',
        'NGN' => '₦',
        'KES' => 'KSh',
        'GHS' => '₵',
        'ZAR' => 'R',
    ],
    'decimal_places' => [
        'XAF' => 0,
        'USD' => 2,
        'EUR' => 2,
        'GBP' => 2,
        'CAD' => 2,
        'NGN' => 2,
        'KES' => 2,
        'GHS' => 2,
        'ZAR' => 2,
    ],
    'fx_margin_default' => env('FX_MARGIN_DEFAULT', 0.025),
    'cache_ttl' => env('FX_CACHE_TTL', 3600),
];

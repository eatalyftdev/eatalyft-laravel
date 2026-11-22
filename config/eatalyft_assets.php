<?php

return [

    /*
    |--------------------------------------------------------------------------
    | EataLyft Centralized Asset Manifest
    |--------------------------------------------------------------------------
    |
    | This manifest organizes all image references by category, providing
    | stable asset identifiers so image paths are not scattered throughout
    | Blade files. Each entry maps a logical key to a public asset path
    | and an optional fallback path used when the primary asset is missing.
    |
    | All paths are relative to the `public/` directory and should be
    | resolved with Laravel's `asset()` helper.
    |
    */

    'path' => 'assets/eatalyft',

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    */
    'branding' => [
        'logo' => 'branding/logo.svg',
        'favicon' => 'favicon.ico',
    ],

    /*
    |--------------------------------------------------------------------------
    | Placeholders / Fallbacks
    |--------------------------------------------------------------------------
    */
    'placeholders' => [
        'avatar' => 'placeholders/avatar.svg',
        'operator_logo' => 'placeholders/operator-logo.svg',
        'destination' => 'placeholders/destination.svg',
    ],

    /*
    |--------------------------------------------------------------------------
    | Buses
    |--------------------------------------------------------------------------
    */
    'buses' => [
        'coach_premium' => 'buses/coaches/coach-premium.svg',
        'coach_interior' => 'buses/interiors/coach-interior.svg',
        'passenger_seats' => 'buses/seats/passenger-seats.svg',
        'transport_terminal' => 'buses/terminals/transport-terminal.svg',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tickets
    |--------------------------------------------------------------------------
    */
    'tickets' => [
        'digital_ticket' => 'tickets/ticket-previews/digital-ticket.svg',
        'boarding_pass' => 'tickets/boarding-passes/boarding-pass.svg',
    ],

    /*
    |--------------------------------------------------------------------------
    | eSIM
    |--------------------------------------------------------------------------
    */
    'esim' => [
        'smartphone' => 'esim/devices/smartphone.svg',
        'activation' => 'esim/activation/activation.svg',
        'connectivity_map' => 'esim/destinations/connectivity-map.svg',
        'data_package' => 'esim/packages/data-package.svg',
    ],

    /*
    |--------------------------------------------------------------------------
    | Games
    |--------------------------------------------------------------------------
    */
    'games' => [
        'featured' => 'games/featured/featured-game.svg',
        'category' => 'games/categories/game-category.svg',
    ],

    /*
    |--------------------------------------------------------------------------
    | Destinations
    |--------------------------------------------------------------------------
    */
    'destinations' => [
        'cameroon_highlands' => 'destinations/cameroon-highlands.svg',
    ],

    /*
    |--------------------------------------------------------------------------
    | Banners & Backgrounds
    |--------------------------------------------------------------------------
    */
    'banners' => [
        'travel' => 'banners/travel-banner.svg',
    ],

    'backgrounds' => [
        'hero' => 'backgrounds/hero-bg.svg',
    ],

    /*
    |--------------------------------------------------------------------------
    | Resolution
    |--------------------------------------------------------------------------
    |
    | Resolve a manifest key to a public URL, falling back to a default
    | placeholder when the key is unknown or the file is missing.
    |
    */

    /*
     * Resolve an asset key to a full public URL.
     *
     * @param  string  $key        Dot-notation key, e.g. 'buses.coach_premium'
     * @param  string  $fallback   Optional fallback manifest key
     * @return string
     */
    'resolve' => function ($key, $fallback = null) {
        $config = config('eatalyft_assets');

        $segments = explode('.', $key);
        $value = $config;

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                break;
            }
            $value = $value[$segment];
        }

        // If the resolved value is not a string path, try the fallback
        if (! is_string($value) || $value === '') {
            $value = $fallback ? $config['resolve']($fallback) : $config['placeholders']['destination'];
        }

        // Ensure we have a string path
        if (! is_string($value)) {
            $value = $config['placeholders']['destination'];
        }

        return asset($config['path'] . '/' . $value);
    },
];

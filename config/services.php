<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Service Catalog
    |--------------------------------------------------------------------------
    |
    | This config defines all services offered by EataLyft. Each service has
    | a status that determines how it is presented on the homepage:
    |
    |   - 'active'   - Fully operational; users can complete transactions
    |   - 'coming_soon' - Planned but not yet operational; users can express interest
    |   - 'available'  - Verified workflow exists; users can browse and book
    |
    | Services with status 'coming_soon' must NOT lead to broken pages or
    | pretend to complete transactions. They should show an interest form
    | or notify users when the service launches.
    */

    'services' => [

        /*
         * Bus Booking — Primary Active Service
         */
        'bus_tickets' => [
            'name' => 'Bus Tickets',
            'slug' => 'bus_tickets',
            'status' => 'active',
            'description' => 'Intercity and local bus travel with real-time seat selection and digital tickets.',
            'short_description' => 'Book bus seats across routes with instant confirmation.',
            'icon' => 'buses.coach_premium',
            'cta_text' => 'Book Bus Tickets',
            'cta_url' => '/search?service=bus_tickets',
            'cta_type' => 'primary',
            'features' => [
                'Real-time seat availability',
                'Digital e-tickets',
                'Verified agencies',
            ],
            'order' => 1,
        ],

        /*
         * Flight Booking — Coming Soon
         */
        'flights' => [
            'name' => 'Flights',
            'slug' => 'flights',
            'status' => 'coming_soon',
            'description' => 'Domestic and international flights with flexible booking options.',
            'short_description' => 'Search and book flights — coming soon.',
            'icon' => 'backgrounds.hero_bg',
            'cta_text' => 'Get Notified',
            'cta_url' => '#notify-flights',
            'cta_type' => 'outline',
            'features' => [
                'Domestic & international routes',
                'Flexible booking options',
            ],
            'order' => 2,
        ],

        /*
         * Hotel Reservations — Coming Soon
         */
        'hotels' => [
            'name' => 'Hotels',
            'slug' => 'hotels',
            'status' => 'coming_soon',
            'description' => 'Curated stays with free cancellation on most bookings.',
            'short_description' => 'Book hotels worldwide — coming soon.',
            'icon' => 'buses.terminals.transport_terminal',
            'cta_text' => 'Get Notified',
            'cta_url' => '#notify-hotels',
            'cta_type' => 'outline',
            'features' => [
                '1M+ properties',
                'Free cancellation',
            ],
            'order' => 3,
        ],

        /*
         * eSIM — Coming Soon
         */
        'esim' => [
            'name' => 'eSIM',
            'slug' => 'esim',
            'status' => 'coming_soon',
            'description' => 'Instant data connectivity in 190+ countries. No physical SIM needed.',
            'short_description' => 'Travel connectivity — coming soon.',
            'icon' => 'esim.devices.smartphone',
            'cta_text' => 'Get Notified',
            'cta_url' => '#notify-esim',
            'cta_type' => 'outline',
            'features' => [
                '190+ countries',
                'Instant activation',
            ],
            'order' => 4,
        ],

        /*
         * Games — Coming Soon
         */
        'games' => [
            'name' => 'Games',
            'slug' => 'games',
            'status' => 'coming_soon',
            'description' => 'Casual and premium games for mobile and web.',
            'short_description' => 'Play games on EataLyft — coming soon.',
            'icon' => 'games.featured.featured_game',
            'cta_text' => 'Get Notified',
            'cta_url' => '#notify-games',
            'cta_type' => 'outline',
            'features' => [
                'Casual & premium games',
                'Mobile & web',
            ],
            'order' => 5,
        ],

        /*
         * Gift Cards — Coming Soon
         */
        'gift_cards' => [
            'name' => 'Gift Cards',
            'slug' => 'gift_cards',
            'status' => 'coming_soon',
            'description' => 'Give the gift of travel. No expiry, usable across all services.',
            'short_description' => 'Digital gift cards — coming soon.',
            'icon' => 'tickets.tickets.digital_ticket',
            'cta_text' => 'Get Notified',
            'cta_url' => '#notify-gift-cards',
            'cta_type' => 'outline',
            'features' => [
                'No expiry',
                'Usable across services',
            ],
            'order' => 6,
        ],

        /*
         * Bill Payments — Coming Soon
         */
        'bills' => [
            'name' => 'Bill Payments',
            'slug' => 'bills',
            'status' => 'coming_soon',
            'description' => 'Pay utilities, internet, and mobile bills through EataLyft.',
            'short_description' => 'Pay bills online — coming soon.',
            'icon' => 'buses.coaches.coach_premium',
            'cta_text' => 'Get Notified',
            'cta_url' => '#notify-bills',
            'cta_type' => 'outline',
            'features' => [
                'Utilities & internet',
                'Mobile bills',
            ],
            'order' => 7,
        ],

        /*
         * Grocery Shopping — Coming Soon
         */
        'groceries' => [
            'name' => 'Groceries',
            'slug' => 'groceries',
            'status' => 'coming_soon',
            'description' => 'Order groceries and essentials for home delivery.',
            'short_description' => 'Grocery delivery — coming soon.',
            'icon' => 'backgrounds.hero_bg',
            'cta_text' => 'Get Notified',
            'cta_url' => '#notify-groceries',
            'cta_type' => 'outline',
            'features' => [
                'Home delivery',
                'Fresh essentials',
            ],
            'order' => 8,
        ],

        /*
         * International Shipping — Coming Soon
         */
        'shipping' => [
            'name' => 'International Shipping',
            'slug' => 'shipping',
            'status' => 'coming_soon',
            'description' => 'Send parcels and documents across borders.',
            'short_description' => 'Cross-border shipping — coming soon.',
            'icon' => 'buses.terminals.transport_terminal',
            'cta_text' => 'Get Notified',
            'cta_url' => '#notify-shipping',
            'cta_type' => 'outline',
            'features' => [
                'Cross-border delivery',
                'Parcel & documents',
            ],
            'order' => 9,
        ],

        /*
         * Home Services / Handyman — Coming Soon
         */
        'handyman' => [
            'name' => 'Home Services',
            'slug' => 'handyman',
            'status' => 'coming_soon',
            'description' => 'Verified professionals for repairs, installations, and maintenance.',
            'short_description' => 'Handyman services — coming soon.',
            'icon' => 'destinations.destinations.cameroon_highlands',
            'cta_text' => 'Get Notified',
            'cta_url' => '#notify-handyman',
            'cta_type' => 'outline',
            'features' => [
                'Verified professionals',
                'Same-day service',
            ],
            'order' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Status Labels
    |--------------------------------------------------------------------------
    */
    'status_labels' => [
        'active' => [
            'text' => 'Available Now',
            'color' => 'success',
            'badge_class' => 'bg-success',
        ],
        'coming_soon' => [
            'text' => 'Coming Soon',
            'color' => 'warning',
            'badge_class' => 'bg-warning text-dark',
        ],
        'available' => [
            'text' => 'Available',
            'color' => 'info',
            'badge_class' => 'bg-info text-dark',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Active Services Only
    |--------------------------------------------------------------------------
    |
    | Only services with status 'active' should be presented as bookable.
    | All others are informational only.
    */
    'active_only' => ['bus_tickets'],
];

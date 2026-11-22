<?php

// config/menu.php
return [
    'superadmin' => [
        ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard', 'permission' => 'system.health'],
        ['label' => 'System', 'icon' => 'settings', 'children' => [
            ['label' => 'Settings', 'route' => 'system.settings', 'permission' => 'system.settings.read'],
            ['label' => 'Audit Logs', 'route' => 'audit.index', 'permission' => 'audit.read'],
            ['label' => 'Health', 'route' => 'health', 'permission' => 'system.health'],
            ['label' => 'Roles & Permissions', 'route' => 'roles.index', 'permission' => 'roles.read'],
            ['label' => 'Integrations', 'route' => 'integrations.index', 'permission' => 'integrations.read'],
        ]],
        ['label' => 'Users & KYC', 'icon' => 'users', 'children' => [
            ['label' => 'Users', 'route' => 'users.index', 'permission' => 'users.read'],
            ['label' => 'KYC Reviews', 'route' => 'kyc.index', 'permission' => 'kyc.read'],
        ]],
        ['label' => 'Agencies', 'icon' => 'building', 'children' => [
            ['label' => 'Agencies', 'route' => 'agencies.index', 'permission' => 'agencies.read'],
        ]],
        ['label' => 'Bus', 'icon' => 'bus', 'children' => [
            ['label' => 'Routes', 'route' => 'bus.routes.index', 'permission' => 'bus.routes.read'],
            ['label' => 'Trips', 'route' => 'bus.trips.index', 'permission' => 'bus.trips.read'],
            ['label' => 'Inventory', 'route' => 'bus.inventory.index', 'permission' => 'bus.inventory.read'],
            ['label' => 'Tickets', 'route' => 'bus.tickets.index', 'permission' => 'bus.tickets.read'],
            ['label' => 'Bookings', 'route' => 'bus.bookings.index', 'permission' => 'bus.bookings.read'],
        ]],
        ['label' => 'Hotels', 'icon' => 'hotel', 'children' => [
            ['label' => 'Hotels', 'route' => 'hotels.index', 'permission' => 'hotels.read'],
            ['label' => 'Bookings', 'route' => 'hotels.bookings.index', 'permission' => 'hotels.bookings.read'],
        ]],
        ['label' => 'Listings', 'icon' => 'list', 'children' => [
            ['label' => 'Listings', 'route' => 'listings.index', 'permission' => 'listings.read'],
        ]],
        ['label' => 'eSIM', 'icon' => 'wifi', 'children' => [
            ['label' => 'Providers', 'route' => 'esim.providers.index', 'permission' => 'esim.read'],
            ['label' => 'Plans', 'route' => 'esim.plans.index', 'permission' => 'esim.plans.read'],
            ['label' => 'Orders', 'route' => 'esim.orders.index', 'permission' => 'esim.orders.read'],
        ]],
        ['label' => 'Boxo', 'icon' => 'grid', 'children' => [
            ['label' => 'Mini Apps', 'route' => 'boxo.miniapps.index', 'permission' => 'boxo.miniapps.read'],
        ]],
        ['label' => 'Currencies & FX', 'icon' => 'currency-exchange', 'children' => [
            ['label' => 'Currencies', 'route' => 'currencies.index', 'permission' => 'currencies.read'],
            ['label' => 'Exchange Rates', 'route' => 'fx.rates.index', 'permission' => 'fx.rates.read'],
            ['label' => 'Margins', 'route' => 'fx.margins.index', 'permission' => 'fx.margins.read'],
            ['label' => 'History', 'route' => 'fx.history.index', 'permission' => 'fx.history.read'],
        ]],
    ],
    'admin' => [
        ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard', 'permission' => 'system.health'],
        ['label' => 'System', 'icon' => 'settings', 'children' => [
            ['label' => 'Settings', 'route' => 'system.settings', 'permission' => 'system.settings.read'],
            ['label' => 'Audit Logs', 'route' => 'audit.index', 'permission' => 'audit.read'],
            ['label' => 'Roles & Permissions', 'route' => 'roles.index', 'permission' => 'roles.read'],
            ['label' => 'Integrations', 'route' => 'integrations.index', 'permission' => 'integrations.read'],
        ]],
        ['label' => 'Users & KYC', 'icon' => 'users', 'children' => [
            ['label' => 'Users', 'route' => 'users.index', 'permission' => 'users.read'],
            ['label' => 'KYC Reviews', 'route' => 'kyc.index', 'permission' => 'kyc.read'],
        ]],
        ['label' => 'Agencies', 'icon' => 'building', 'children' => [
            ['label' => 'Agencies', 'route' => 'agencies.index', 'permission' => 'agencies.read'],
        ]],
        ['label' => 'Bus', 'icon' => 'bus', 'children' => [
            ['label' => 'Routes', 'route' => 'bus.routes.index', 'permission' => 'bus.routes.read'],
            ['label' => 'Trips', 'route' => 'bus.trips.index', 'permission' => 'bus.trips.read'],
            ['label' => 'Inventory', 'route' => 'bus.inventory.index', 'permission' => 'bus.inventory.read'],
            ['label' => 'Tickets', 'route' => 'bus.tickets.index', 'permission' => 'bus.tickets.read'],
            ['label' => 'Bookings', 'route' => 'bus.bookings.index', 'permission' => 'bus.bookings.read'],
        ]],
        ['label' => 'Hotels', 'icon' => 'hotel', 'children' => [
            ['label' => 'Hotels', 'route' => 'hotels.index', 'permission' => 'hotels.read'],
            ['label' => 'Bookings', 'route' => 'hotels.bookings.index', 'permission' => 'hotels.bookings.read'],
        ]],
        ['label' => 'Listings', 'icon' => 'list', 'children' => [
            ['label' => 'Listings', 'route' => 'listings.index', 'permission' => 'listings.read'],
        ]],
        ['label' => 'eSIM', 'icon' => 'wifi', 'children' => [
            ['label' => 'Providers', 'route' => 'esim.providers.index', 'permission' => 'esim.read'],
            ['label' => 'Plans', 'route' => 'esim.plans.index', 'permission' => 'esim.plans.read'],
            ['label' => 'Orders', 'route' => 'esim.orders.index', 'permission' => 'esim.orders.read'],
        ]],
        ['label' => 'Boxo', 'icon' => 'grid', 'children' => [
            ['label' => 'Mini Apps', 'route' => 'boxo.miniapps.index', 'permission' => 'boxo.miniapps.read'],
        ]],
        ['label' => 'Currencies & FX', 'icon' => 'currency-exchange', 'children' => [
            ['label' => 'Currencies', 'route' => 'currencies.index', 'permission' => 'currencies.read'],
            ['label' => 'Exchange Rates', 'route' => 'fx.rates.index', 'permission' => 'fx.rates.read'],
            ['label' => 'Margins', 'route' => 'fx.margins.index', 'permission' => 'fx.margins.read'],
            ['label' => 'History', 'route' => 'fx.history.index', 'permission' => 'fx.history.read'],
        ]],
    ],
    'support_agent' => [
        ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard', 'permission' => 'system.health'],
        ['label' => 'Users', 'icon' => 'users', 'route' => 'users.index', 'permission' => 'users.read'],
        ['label' => 'KYC', 'icon' => 'shield-check', 'route' => 'kyc.index', 'permission' => 'kyc.read'],
        ['label' => 'Notifications', 'icon' => 'bell', 'route' => 'notifications.index', 'permission' => 'notifications.read'],
        ['label' => 'Audit Logs', 'icon' => 'clipboard-data', 'route' => 'audit.index', 'permission' => 'audit.read'],
        ['label' => 'Hotel Bookings', 'icon' => 'hotel', 'route' => 'hotels.bookings.index', 'permission' => 'hotels.bookings.read'],
        ['label' => 'Bus Bookings', 'icon' => 'bus', 'route' => 'bus.bookings.index', 'permission' => 'bus.bookings.read'],
        ['label' => 'Currencies & FX', 'icon' => 'currency-exchange', 'children' => [
            ['label' => 'Currencies', 'route' => 'currencies.index', 'permission' => 'currencies.read'],
            ['label' => 'Exchange Rates', 'route' => 'fx.rates.index', 'permission' => 'fx.rates.read'],
        ]],
    ],
    'kyc_reviewer' => [
        ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard', 'permission' => 'system.health'],
        ['label' => 'KYC Reviews', 'icon' => 'shield-check', 'route' => 'kyc.index', 'permission' => 'kyc.read'],
        ['label' => 'Users', 'icon' => 'users', 'route' => 'users.index', 'permission' => 'users.read'],
        ['label' => 'Notifications', 'icon' => 'bell', 'route' => 'notifications.index', 'permission' => 'notifications.read'],
        ['label' => 'Currencies & FX', 'icon' => 'currency-exchange', 'children' => [
            ['label' => 'Currencies', 'route' => 'currencies.index', 'permission' => 'currencies.read'],
            ['label' => 'Exchange Rates', 'route' => 'fx.rates.index', 'permission' => 'fx.rates.read'],
        ]],
    ],
    'listing_moderator' => [
        ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard', 'permission' => 'system.health'],
        ['label' => 'Listings', 'icon' => 'list', 'children' => [
            ['label' => 'Listings', 'route' => 'listings.index', 'permission' => 'listings.read'],
        ]],
        ['label' => 'Users', 'icon' => 'users', 'route' => 'users.index', 'permission' => 'users.read'],
        ['label' => 'KYC', 'icon' => 'shield-check', 'route' => 'kyc.index', 'permission' => 'kyc.read'],
        ['label' => 'Notifications', 'icon' => 'bell', 'route' => 'notifications.index', 'permission' => 'notifications.read'],
        ['label' => 'Currencies & FX', 'icon' => 'currency-exchange', 'children' => [
            ['label' => 'Currencies', 'route' => 'currencies.index', 'permission' => 'currencies.read'],
            ['label' => 'Exchange Rates', 'route' => 'fx.rates.index', 'permission' => 'fx.rates.read'],
        ]],
    ],
    'agency_manager' => [
        ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard', 'permission' => 'system.health'],
        ['label' => 'My Agency', 'icon' => 'building', 'children' => [
        ]],
        ['label' => 'Bus', 'icon' => 'bus', 'children' => [
            ['label' => 'Routes', 'route' => 'bus.routes.index', 'permission' => 'bus.routes.read'],
            ['label' => 'Trips', 'route' => 'bus.trips.index', 'permission' => 'bus.trips.read'],
            ['label' => 'Inventory', 'route' => 'bus.inventory.index', 'permission' => 'bus.inventory.read'],
            ['label' => 'Tickets', 'route' => 'bus.tickets.index', 'permission' => 'bus.tickets.read'],
            ['label' => 'Bookings', 'route' => 'bus.bookings.index', 'permission' => 'bus.bookings.read'],
        ]],
        ['label' => 'Hotels', 'icon' => 'hotel', 'children' => [
            ['label' => 'Hotels', 'route' => 'hotels.index', 'permission' => 'hotels.read'],
            ['label' => 'Rooms', 'route' => 'hotels.rooms.index', 'permission' => 'hotels.rooms.read'],
            ['label' => 'Rates', 'route' => 'hotels.rates.index', 'permission' => 'hotels.rates.read'],
            ['label' => 'Availability', 'route' => 'hotels.availability.index', 'permission' => 'hotels.availability.read'],
            ['label' => 'Bookings', 'route' => 'hotels.bookings.index', 'permission' => 'hotels.bookings.read'],
        ]],
        ['label' => 'Users', 'icon' => 'users', 'route' => 'users.index', 'permission' => 'users.read'],
        ['label' => 'KYC', 'icon' => 'shield-check', 'route' => 'kyc.index', 'permission' => 'kyc.read'],
        ['label' => 'Notifications', 'icon' => 'bell', 'route' => 'notifications.index', 'permission' => 'notifications.read'],
        ['label' => 'Currencies & FX', 'icon' => 'currency-exchange', 'children' => [
            ['label' => 'Currencies', 'route' => 'currencies.index', 'permission' => 'currencies.read'],
            ['label' => 'Exchange Rates', 'route' => 'fx.rates.index', 'permission' => 'fx.rates.read'],
        ]],
    ],
    'branch_manager' => [
        ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard', 'permission' => 'system.health'],
        ['label' => 'Bus', 'icon' => 'bus', 'children' => [
            ['label' => 'Routes', 'route' => 'bus.routes.index', 'permission' => 'bus.routes.read'],
            ['label' => 'Trips', 'route' => 'bus.trips.index', 'permission' => 'bus.trips.read'],
            ['label' => 'Inventory', 'route' => 'bus.inventory.index', 'permission' => 'bus.inventory.read'],
            ['label' => 'Tickets', 'route' => 'bus.tickets.index', 'permission' => 'bus.tickets.read'],
            ['label' => 'Bookings', 'route' => 'bus.bookings.index', 'permission' => 'bus.bookings.read'],
        ]],
        ['label' => 'Hotels', 'icon' => 'hotel', 'children' => [
            ['label' => 'Hotels', 'route' => 'hotels.index', 'permission' => 'hotels.read'],
            ['label' => 'Rooms', 'route' => 'hotels.rooms.index', 'permission' => 'hotels.rooms.read'],
            ['label' => 'Rates', 'route' => 'hotels.rates.index', 'permission' => 'hotels.rates.read'],
            ['label' => 'Availability', 'route' => 'hotels.availability.index', 'permission' => 'hotels.availability.read'],
            ['label' => 'Bookings', 'route' => 'hotels.bookings.index', 'permission' => 'hotels.bookings.read'],
        ]],
        ['label' => 'Users', 'icon' => 'users', 'route' => 'users.index', 'permission' => 'users.read'],
        ['label' => 'KYC', 'icon' => 'shield-check', 'route' => 'kyc.index', 'permission' => 'kyc.read'],
        ['label' => 'Notifications', 'icon' => 'bell', 'route' => 'notifications.index', 'permission' => 'notifications.read'],
        ['label' => 'Currencies & FX', 'icon' => 'currency-exchange', 'children' => [
            ['label' => 'Currencies', 'route' => 'currencies.index', 'permission' => 'currencies.read'],
            ['label' => 'Exchange Rates', 'route' => 'fx.rates.index', 'permission' => 'fx.rates.read'],
        ]],
    ],
    'ticket_agent' => [
        ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard', 'permission' => 'system.health'],
        ['label' => 'Bus Counter', 'icon' => 'bus', 'children' => [
            ['label' => 'Routes', 'route' => 'bus.routes.index', 'permission' => 'bus.routes.read'],
            ['label' => 'Trips', 'route' => 'bus.trips.index', 'permission' => 'bus.trips.read'],
            ['label' => 'Inventory', 'route' => 'bus.inventory.index', 'permission' => 'bus.inventory.read'],
            ['label' => 'Sell Tickets', 'route' => 'bus.tickets.create', 'permission' => 'bus.tickets.write'],
            ['label' => 'My Tickets', 'route' => 'bus.tickets.index', 'permission' => 'bus.tickets.read'],
            ['label' => 'Bookings', 'route' => 'bus.bookings.index', 'permission' => 'bus.bookings.read'],
        ]],
        ['label' => 'Users', 'icon' => 'users', 'route' => 'users.index', 'permission' => 'users.read'],
        ['label' => 'KYC', 'icon' => 'shield-check', 'route' => 'kyc.index', 'permission' => 'kyc.read'],
        ['label' => 'Notifications', 'icon' => 'bell', 'route' => 'notifications.index', 'permission' => 'notifications.read'],
        ['label' => 'Currencies & FX', 'icon' => 'currency-exchange', 'children' => [
            ['label' => 'Currencies', 'route' => 'currencies.index', 'permission' => 'currencies.read'],
            ['label' => 'Exchange Rates', 'route' => 'fx.rates.index', 'permission' => 'fx.rates.read'],
        ]],
    ],
    'ticket_scanner' => [
        ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard', 'permission' => 'system.health'],
        ['label' => 'Scan Tickets', 'icon' => 'camera', 'route' => 'dashboard', 'permission' => 'bus.tickets.scan'],
        ['label' => 'Trips', 'icon' => 'bus', 'route' => 'bus.trips.index', 'permission' => 'bus.trips.read'],
        ['label' => 'Currencies', 'icon' => 'currency-exchange', 'route' => 'currencies.index', 'permission' => 'currencies.read'],
    ],
    'hotel_manager' => [
        ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard', 'permission' => 'system.health'],
        ['label' => 'Hotels', 'icon' => 'hotel', 'children' => [
            ['label' => 'Hotels', 'route' => 'hotels.index', 'permission' => 'hotels.read'],
            ['label' => 'Rooms', 'route' => 'hotels.rooms.index', 'permission' => 'hotels.rooms.read'],
            ['label' => 'Rates', 'route' => 'hotels.rates.index', 'permission' => 'hotels.rates.read'],
            ['label' => 'Availability', 'route' => 'hotels.availability.index', 'permission' => 'hotels.availability.read'],
            ['label' => 'Bookings', 'route' => 'hotels.bookings.index', 'permission' => 'hotels.bookings.read'],
        ]],
        ['label' => 'Users', 'icon' => 'users', 'route' => 'users.index', 'permission' => 'users.read'],
        ['label' => 'KYC', 'icon' => 'shield-check', 'route' => 'kyc.index', 'permission' => 'kyc.read'],
        ['label' => 'Notifications', 'icon' => 'bell', 'route' => 'notifications.index', 'permission' => 'notifications.read'],
        ['label' => 'Currencies & FX', 'icon' => 'currency-exchange', 'children' => [
            ['label' => 'Currencies', 'route' => 'currencies.index', 'permission' => 'currencies.read'],
            ['label' => 'Exchange Rates', 'route' => 'fx.rates.index', 'permission' => 'fx.rates.read'],
        ]],
    ],
    'handyman_provider' => [
        ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard', 'permission' => 'system.health'],
        ['label' => 'My Services', 'icon' => 'tool', 'children' => [
            ['label' => 'Services', 'route' => 'handyman.services.index', 'permission' => 'handyman.providers.read'],
            ['label' => 'Bookings', 'route' => 'handyman.bookings.index', 'permission' => 'handyman.bookings.read'],
        ]],
        ['label' => 'Users', 'icon' => 'users', 'route' => 'users.index', 'permission' => 'users.read'],
        ['label' => 'KYC', 'icon' => 'shield-check', 'route' => 'kyc.index', 'permission' => 'kyc.read'],
        ['label' => 'Notifications', 'icon' => 'bell', 'route' => 'notifications.index', 'permission' => 'notifications.read'],
        ['label' => 'Currencies & FX', 'icon' => 'currency-exchange', 'children' => [
            ['label' => 'Currencies', 'route' => 'currencies.index', 'permission' => 'currencies.read'],
            ['label' => 'Exchange Rates', 'route' => 'fx.rates.index', 'permission' => 'fx.rates.read'],
        ]],
    ],
    'customer' => [
        ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard', 'permission' => 'system.health'],
        ['label' => 'Profile', 'icon' => 'user', 'route' => 'profile.show', 'permission' => 'users.read'],
        ['label' => 'KYC', 'icon' => 'shield-check', 'route' => 'kyc.index', 'permission' => 'kyc.read'],
        ['label' => 'Notifications', 'icon' => 'bell', 'route' => 'notifications.index', 'permission' => 'notifications.read'],
        ['label' => 'Hotel Bookings', 'icon' => 'hotel', 'route' => 'hotels.bookings.index', 'permission' => 'hotels.bookings.read'],
        ['label' => 'Bus Bookings', 'icon' => 'bus', 'route' => 'bus.bookings.index', 'permission' => 'bus.bookings.read'],
        ['label' => 'eSIM Orders', 'icon' => 'wifi', 'route' => 'esim.orders.index', 'permission' => 'esim.orders.read'],
    ],
    'creator' => [
        ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard', 'permission' => 'system.health'],
        ['label' => 'Content', 'icon' => 'edit', 'children' => [
            ['label' => 'My Content', 'route' => 'dashboard', 'permission' => 'creator.content.read'],
            ['label' => 'Analytics', 'route' => 'dashboard', 'permission' => 'creator.analytics.read'],
            ['label' => 'Payouts', 'route' => 'dashboard', 'permission' => 'creator.payouts.read'],
        ]],
        ['label' => 'Profile', 'icon' => 'user', 'route' => 'profile.show', 'permission' => 'users.read'],
        ['label' => 'KYC', 'icon' => 'shield-check', 'route' => 'kyc.index', 'permission' => 'kyc.read'],
        ['label' => 'Notifications', 'icon' => 'bell', 'route' => 'notifications.index', 'permission' => 'notifications.read'],
        ['label' => 'Currencies & FX', 'icon' => 'currency-exchange', 'children' => [
            ['label' => 'Currencies', 'route' => 'currencies.index', 'permission' => 'currencies.read'],
            ['label' => 'Exchange Rates', 'route' => 'fx.rates.index', 'permission' => 'fx.rates.read'],
        ]],
    ],
];

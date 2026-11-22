<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // System
            'system.admin',
            'system.settings.read',
            'system.settings.write',
            'system.audit.read',
            'system.health',

            // Users
            'users.read',
            'users.write',
            'users.delete',
            'users.kyc.read',
            'users.kyc.write',

            // Roles & Permissions
            'roles.read',
            'roles.write',
            'roles.delete',

            // KYC
            'kyc.read',
            'kyc.write',
            'kyc.approve',
            'kyc.reject',

            // Notifications
            'notifications.read',
            'notifications.write',
            'notifications.send',

            // Audit Logs
            'audit.read',

            // Agencies
            'agencies.read',
            'agencies.write',
            'agencies.delete',
            'agencies.branches.read',
            'agencies.branches.write',
            'agencies.branches.delete',
            'agencies.staff.read',
            'agencies.staff.write',
            'agencies.staff.delete',

            // Bus
            'bus.routes.read',
            'bus.routes.write',
            'bus.routes.delete',
            'bus.trips.read',
            'bus.trips.write',
            'bus.trips.delete',
            'bus.inventory.read',
            'bus.inventory.write',
            'bus.tickets.read',
            'bus.tickets.write',
            'bus.tickets.cancel',
            'bus.tickets.scan',
            'bus.bookings.read',
            'bus.bookings.write',
            'bus.bookings.confirm',
            'bus.bookings.cancel',

            // Hotels
            'hotels.read',
            'hotels.write',
            'hotels.delete',
            'hotels.rooms.read',
            'hotels.rooms.write',
            'hotels.rooms.delete',
            'hotels.rates.read',
            'hotels.rates.write',
            'hotels.availability.read',
            'hotels.availability.write',
            'hotels.bookings.read',
            'hotels.bookings.write',
            'hotels.bookings.confirm',
            'hotels.bookings.cancel',

            // Listings
            'listings.read',
            'listings.write',
            'listings.delete',
            'listings.publish',
            'listings.moderate',

            // eSIM
            'esim.read',
            'esim.write',
            'esim.delete',
            'esim.plans.read',
            'esim.plans.write',
            'esim.orders.read',
            'esim.orders.write',

            // Boxo
            'boxo.read',
            'boxo.write',
            'boxo.miniapps.read',
            'boxo.miniapps.write',

            // Integrations Vault
            'integrations.read',
            'integrations.write',
            'integrations.delete',

            // Currencies & FX
            'currencies.read',
            'currencies.write',
            'currencies.delete',
            'fx.rates.read',
            'fx.rates.write',
            'fx.margins.read',
            'fx.margins.write',
            'fx.history.read',

            // Handyman (deferred - schema only)
            'handyman.read',
            'handyman.write',
            'handyman.delete',
            'handyman.providers.read',
            'handyman.providers.write',
            'handyman.bookings.read',
            'handyman.bookings.write',

            // Creator
            'creator.content.read',
            'creator.content.write',
            'creator.analytics.read',
            'creator.payouts.read',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $allPerms = Permission::where('guard_name', 'web')->pluck('name')->toArray();

        $roles = [
            // superadmin: all permissions, can create custom sub-roles
            'superadmin' => $allPerms,

            // admin: all except system.admin
            'admin' => array_filter($allPerms, fn ($p) => $p !== 'system.admin'),

            // support_agent: read users, KYC, notifications, audit, bookings
            'support_agent' => [
                'users.read', 'users.kyc.read',
                'kyc.read', 'kyc.write',
                'notifications.read', 'notifications.write', 'notifications.send',
                'audit.read',
                'hotels.bookings.read', 'bus.bookings.read',
                'currencies.read', 'fx.rates.read',
            ],

            // kyc_reviewer: KYC only
            'kyc_reviewer' => [
                'users.read', 'users.kyc.read',
                'kyc.read', 'kyc.write', 'kyc.approve', 'kyc.reject',
                'notifications.read', 'notifications.write',
                'currencies.read', 'fx.rates.read',
            ],

            // listing_moderator: listings + users/KYC/notifications
            'listing_moderator' => [
                'listings.read', 'listings.moderate', 'listings.publish',
                'users.read', 'kyc.read',
                'notifications.read', 'notifications.write',
                'currencies.read', 'fx.rates.read',
            ],

            // agency_manager: full agency scope (agencies, branches, staff, bus, hotels)
            'agency_manager' => [
                'agencies.read', 'agencies.write', 'agencies.branches.read', 'agencies.branches.write', 'agencies.staff.read', 'agencies.staff.write',
                'bus.routes.read', 'bus.routes.write', 'bus.trips.read', 'bus.trips.write',
                'bus.inventory.read', 'bus.inventory.write', 'bus.tickets.read', 'bus.tickets.write', 'bus.tickets.cancel',
                'bus.bookings.read', 'bus.bookings.write', 'bus.bookings.confirm', 'bus.bookings.cancel',
                'hotels.read', 'hotels.write', 'hotels.rooms.read', 'hotels.rooms.write',
                'hotels.rates.read', 'hotels.rates.write', 'hotels.availability.read', 'hotels.availability.write',
                'hotels.bookings.read', 'hotels.bookings.write', 'hotels.bookings.confirm', 'hotels.bookings.cancel',
                'users.read', 'kyc.read', 'notifications.read', 'notifications.write', 'notifications.send',
                'currencies.read', 'fx.rates.read',
            ],

            // branch_manager: scoped to their branch
            'branch_manager' => [
                'agencies.branches.read',
                'bus.routes.read', 'bus.trips.read', 'bus.inventory.read',
                'bus.tickets.read', 'bus.tickets.write', 'bus.tickets.cancel', 'bus.tickets.scan',
                'bus.bookings.read', 'bus.bookings.write', 'bus.bookings.confirm', 'bus.bookings.cancel',
                'hotels.read', 'hotels.rooms.read', 'hotels.rates.read', 'hotels.availability.read',
                'hotels.bookings.read', 'hotels.bookings.write', 'hotels.bookings.confirm', 'hotels.bookings.cancel',
                'users.read', 'kyc.read', 'notifications.read', 'notifications.write', 'notifications.send',
                'currencies.read', 'fx.rates.read',
            ],

            // ticket_agent: sell/scan tickets at counter
            'ticket_agent' => [
                'bus.routes.read', 'bus.trips.read', 'bus.inventory.read',
                'bus.tickets.read', 'bus.tickets.write', 'bus.tickets.cancel', 'bus.tickets.scan',
                'bus.bookings.read', 'bus.bookings.write', 'bus.bookings.confirm', 'bus.bookings.cancel',
                'users.read', 'kyc.read', 'notifications.read', 'notifications.write',
                'currencies.read', 'fx.rates.read',
            ],

            // ticket_scanner: scan only
            'ticket_scanner' => [
                'bus.tickets.scan',
                'bus.trips.read',
                'currencies.read',
            ],

            // hotel_manager: hotels scope
            'hotel_manager' => [
                'hotels.read', 'hotels.write', 'hotels.rooms.read', 'hotels.rooms.write',
                'hotels.rates.read', 'hotels.rates.write', 'hotels.availability.read', 'hotels.availability.write',
                'hotels.bookings.read', 'hotels.bookings.write', 'hotels.bookings.confirm', 'hotels.bookings.cancel',
                'users.read', 'kyc.read', 'notifications.read', 'notifications.write', 'notifications.send',
                'currencies.read', 'fx.rates.read',
            ],

            // handyman_provider: deferred (schema only)
            'handyman_provider' => [
                'handyman.providers.read', 'handyman.providers.write',
                'handyman.bookings.read', 'handyman.bookings.write',
                'users.read', 'kyc.read', 'notifications.read', 'notifications.write',
                'currencies.read', 'fx.rates.read',
            ],

            // customer: own profile, KYC, bookings, orders
            'customer' => [
                'users.read', 'kyc.read', 'kyc.write',
                'notifications.read',
                'bus.bookings.read', 'bus.tickets.read',
                'hotels.bookings.read',
                'esim.orders.read',
                'currencies.read', 'fx.rates.read',
            ],

            // creator: content + analytics
            'creator' => [
                'creator.content.read', 'creator.content.write',
                'creator.analytics.read', 'creator.payouts.read',
                'users.read', 'kyc.read', 'kyc.write',
                'notifications.read', 'notifications.write',
                'currencies.read', 'fx.rates.read',
            ],
        ];

        foreach ($roles as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }
    }
}
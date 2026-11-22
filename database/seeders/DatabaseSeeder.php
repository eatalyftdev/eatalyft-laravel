<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AppSettingsSeeder::class,
            AdminUserSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@eatalyft.com',
            'role' => 'superadmin',
            'status' => 'active',
            'created_by_admin' => true,
        ])->assignRole('superadmin');

        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin2@eatalyft.com',
            'role' => 'admin',
            'status' => 'active',
            'created_by_admin' => true,
        ])->assignRole('admin');

        User::factory()->create([
            'name' => 'Support Agent',
            'email' => 'support@eatalyft.com',
            'role' => 'support_agent',
            'status' => 'active',
            'created_by_admin' => true,
        ])->assignRole('support_agent');

        User::factory()->create([
            'name' => 'KYC Reviewer',
            'email' => 'kyc@eatalyft.com',
            'role' => 'kyc_reviewer',
            'status' => 'active',
            'created_by_admin' => true,
        ])->assignRole('kyc_reviewer');

        User::factory()->create([
            'name' => 'Listing Moderator',
            'email' => 'moderator@eatalyft.com',
            'role' => 'listing_moderator',
            'status' => 'active',
            'created_by_admin' => true,
        ])->assignRole('listing_moderator');

        User::factory()->create([
            'name' => 'Agency Manager',
            'email' => 'agency@eatalyft.com',
            'role' => 'agency_manager',
            'status' => 'active',
            'created_by_admin' => true,
        ])->assignRole('agency_manager');

        User::factory()->create([
            'name' => 'Branch Manager',
            'email' => 'branch@eatalyft.com',
            'role' => 'branch_manager',
            'status' => 'active',
            'created_by_admin' => true,
        ])->assignRole('branch_manager');

        User::factory()->create([
            'name' => 'Ticket Agent',
            'email' => 'ticket@eatalyft.com',
            'role' => 'ticket_agent',
            'status' => 'active',
            'created_by_admin' => true,
        ])->assignRole('ticket_agent');

        User::factory()->create([
            'name' => 'Ticket Scanner',
            'email' => 'scanner@eatalyft.com',
            'role' => 'ticket_scanner',
            'status' => 'active',
            'created_by_admin' => true,
        ])->assignRole('ticket_scanner');

        User::factory()->create([
            'name' => 'Hotel Manager',
            'email' => 'hotel@eatalyft.com',
            'role' => 'hotel_manager',
            'status' => 'active',
            'created_by_admin' => true,
        ])->assignRole('hotel_manager');

        User::factory()->create([
            'name' => 'Handyman Provider',
            'email' => 'handyman@eatalyft.com',
            'role' => 'handyman_provider',
            'status' => 'active',
            'created_by_admin' => true,
        ])->assignRole('handyman_provider');

        User::factory()->create([
            'name' => 'Creator User',
            'email' => 'creator@eatalyft.com',
            'role' => 'creator',
            'status' => 'active',
        ])->assignRole('creator');

        User::factory()->create([
            'name' => 'Test Customer',
            'email' => 'customer@eatalyft.com',
            'role' => 'customer',
            'status' => 'active',
        ])->assignRole('customer');
    }
}
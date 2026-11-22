<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the seeder.
     *
     * Creates or updates the local administrator account using environment
     * variables. Idempotent — running multiple times will not create
     * duplicates or overwrite an existing administrator's password.
     *
     * Required environment variables:
     *   ADMIN_NAME     - Administrator display name
     *   ADMIN_EMAIL    - Administrator email address
     *   ADMIN_PASSWORD - Administrator password (minimum 8 characters)
     *
     * @throws \Exception If required environment variables are missing
     */
    public function run(): void
    {
        $name = env('ADMIN_NAME');
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (empty($name) || empty($email) || empty($password)) {
            $this->command->warn(
                'AdminUserSeeder skipped: ADMIN_NAME, ADMIN_EMAIL, and ADMIN_PASSWORD ' .
                'environment variables are not set. ' .
                'See docs/ADMIN_ACCESS.md for configuration instructions.'
            );

            return;
        }

        // Check if an administrator already exists with this email
        $existingAdmin = User::where('email', $email)->first();

        if ($existingAdmin) {
            // Idempotent: do not overwrite an existing administrator's password
            // Only ensure the role is correct
            if (!$existingAdmin->hasRole('superadmin') && !$existingAdmin->hasRole('admin')) {
                $existingAdmin->assignRole('admin');
            }

            $this->command->info(
                "Administrator account already exists: {$email} (role: {$existingAdmin->getRoleNames()->first()})"
            );

            return;
        }

        // Create the administrator account
        $admin = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
            'created_by_admin' => true,
        ]);

        // Assign the admin role
        $admin->assignRole('admin');

        $this->command->info(
            "Administrator account created: {$email} (role: admin)"
        );

        $this->command->warn(
            'IMPORTANT: Change the administrator password after first login. ' .
            'Do not commit ADMIN_PASSWORD to version control.'
        );
    }
}

<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment() !== 'local') {
            throw new \RuntimeException('DemoSeeder only runs in local environment');
        }
        $password = 'Password123!';
        $roles = ['superadmin','admin','agency_manager','branch_manager','ticket_agent','ticket_scanner','hotel_manager','kyc_reviewer','support_agent','listing_moderator','handyman_provider','creator','customer'];
        foreach ($roles as $role) {
            $email = str_replace('_','',$role).'@eatalyft.test';
            $user = User::firstOrCreate(['email'=>$email], ['name'=>ucwords(str_replace('_',' ',$role)), 'password'=>bcrypt($password), 'role'=>$role, 'email_verified_at'=>now()]);
            $user->syncRoles([$role]);
        }
        $this->command->info('Demo credentials (local only): password = '.$password);
        foreach ($roles as $role) $this->command->info(str_replace('_','',$role).'@eatalyft.test');
    }
}

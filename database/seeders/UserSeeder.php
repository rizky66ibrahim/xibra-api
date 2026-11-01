<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $username = config('owner.username');
        $password = config('owner.password');

        if (! $username || ! $password) {
            $this->command?->error('Owner username or password is not set in the configuration. Please set them in config/owner.php.');

            return;
        }

        $owner = User::firstOrNew(['username' => $username]);

        // Force fill the attributes to ensure they are set correctly
        $owner->forceFill([
            'name' => config('owner.name'),
            'email' => config('owner.email'),
            'phone_number' => config('owner.phone_number'),
            'password' => $password,
            'role' => 'admin',
            'is_active' => true,
        ])->save();

        $this->command?->info("Owner {$username} has been created or updated successfully.");
    }
}

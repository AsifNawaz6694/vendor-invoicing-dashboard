<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure super_admin role exists
        $superAdminRole = Role::firstOrCreate(
            ['slug' => 'super_admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Has complete access to all system features and bypasses all permission checks',
                'is_system' => true
            ]
        );

        // Create or update the admin user
        $adminUser = User::updateOrCreate(
            ['email' => 'asif@bargoventures.com'],
            [
                'name' => 'Asif',
                'password' => Hash::make('123456789'),
                'email_verified_at' => now(),
                'is_admin' => true,
                'is_active' => true,
            ]
        );

        // Assign super_admin role to the user
        $adminUser->assignRole($superAdminRole);

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: asif@bargoventures.com');
        $this->command->info('Password: 123456789');
        $this->command->info('Role: Super Admin');
    }
}
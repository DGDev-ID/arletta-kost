<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $ownerRole = Role::where('name', 'owner')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $superadminRole = Role::where('name', 'superadmin')->first();

        $owner = User::create([
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);
        $owner->roles()->attach($ownerRole);

        $superadmin = User::create([
            'name' => 'Super Admin User',
            'email' => 'superadmin@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);
        $superadmin->roles()->attach($superadminRole);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);
        $admin->roles()->attach($adminRole);
    }
}

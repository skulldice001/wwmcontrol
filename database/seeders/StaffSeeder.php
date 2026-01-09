<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Staff;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Staff::create([
            'account' => 'master',
            'name' => 'Master User',
            'email' => 'master@example.com',
            'password' => Hash::make('123456'),
            'role' => Staff::ROLE_MASTER,
        ]);

        Staff::create([
            'account' => 'admin',
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('123456'),
            'role' => Staff::ROLE_ADMIN,
        ]);

        Staff::create([
            'account' => 'observer',
            'name' => 'Observer User',
            'email' => 'observer@example.com',
            'password' => Hash::make('123456'),
            'role' => Staff::ROLE_OBSERVER,
        ]);
    }
}

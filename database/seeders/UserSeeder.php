<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Delete existing admin user to prevent duplication errors if run multiple times
        User::where('username', 'admin')->forceDelete();

        User::create([
            'username' => 'admin',
            'email' => 'admin@sigidental.id',
            'full_name' => 'Admin System',
            'password' => Hash::make('admin'), // The model also casts to hashed, but we explicitly hash here or let model do it.
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}

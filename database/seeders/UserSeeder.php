<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::factory()->create([
            'name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@gmail.com',
            'password' => 'admin1234', // Ensure password is hashed
            'no_handphone' => null,
            'role' => 'admin',
        ]);

        // Create penjaga user
        User::factory()->create([
            'name' => 'John',
            'last_name' => 'Doe',
            'email' => 'johndoe@gmail.com',
            'password' => 'penjaga1234', // Ensure password is hashed
            'no_handphone' => '08809823755',
            'role' => 'penjaga',
        ]);
    }
}

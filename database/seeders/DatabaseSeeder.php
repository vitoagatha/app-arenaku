<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\CourtsSeeder;
use Database\Seeders\LapanganSeeder;
use Database\Seeders\JadwalLapanganSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            LapanganSeeder::class,
            CourtsSeeder::class,
            JadwalLapanganSeeder::class,
        ]);
    }
}

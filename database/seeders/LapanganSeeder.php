<?php

namespace Database\Seeders;

use App\Models\Lapangan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class LapanganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing files in the uploads directory
        Storage::disk('public')->deleteDirectory('uploads');
        Storage::disk('public')->makeDirectory('uploads');

        // Create 10 sample lapangan records
        Lapangan::factory()->count(5)->create();
    }
}

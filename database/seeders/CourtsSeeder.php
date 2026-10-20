<?php

namespace Database\Seeders;

use App\Models\Court;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CourtsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tambahkan contoh data untuk courts
        $courts = [
            ['nama_courts' => 'Court A'],
            ['nama_courts' => 'Court B'],
            ['nama_courts' => 'Court C'],
        ];

        // Masukkan data ke dalam database
        foreach ($courts as $court) {
            Court::create($court);
        }
    }
}

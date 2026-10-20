<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Court;
use App\Models\Lapangan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class JadwalLapanganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courts = Court::all();
        $lapangans = Lapangan::all();

        foreach ($lapangans as $lapangan) {
            foreach ($courts as $court) {
                DB::table('jadwal_lapangan')->insert([
                    'lapangan_id' => $lapangan->id,
                    'court_id' => $court->id,
                    'jam_awal' => Carbon::createFromTime(rand(6, 18), 0, 0)->toTimeString(),
                    'jam_akhir' => Carbon::createFromTime(rand(19, 23), 0, 0)->toTimeString(),
                    'tanggal' => Carbon::today()->addDays(rand(0, 30))->toDateString(),
                    'status' => 'ready',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}

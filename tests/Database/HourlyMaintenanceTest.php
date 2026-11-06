<?php

namespace Tests\Database;

use App\Models\User;
use App\Services\CourtOperations;
use App\Services\HourlyBooking;
use App\Services\HourlyMaintenance;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

class HourlyMaintenanceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_scheduler_catches_up_and_admin_sees_stale_expiry(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2030-05-01 08:00', 'Asia/Jakarta'));
        try {
            $admin = $this->user('admin');
            $keeper = $this->user('penjaga');
            $venue = DB::table('lapangan')->insertGetId([
                'nama_lapangan' => 'Scheduler fixture', 'alamat' => 'Test', 'user_id' => $keeper->id,
                'regular_opens_at' => '10:00', 'regular_closes_at' => '13:00', 'booking_contact' => '+628000000000',
            ]);
            $court = DB::table('venue_courts')->insertGetId([
                'lapangan_id' => $venue, 'name' => 'Scheduler court', 'is_active' => true,
            ]);
            $ops = app(CourtOperations::class);
            $maintenance = app(HourlyMaintenance::class);
            $legacyCourt = DB::table('courts')->insertGetId(['nama_courts' => 'Cutover legacy']);
            $legacy = DB::table('jadwal_lapangan')->insertGetId([
                'lapangan_id' => $venue, 'court_id' => $legacyCourt, 'tanggal' => '2030-05-01',
                'jam_awal' => '10:00', 'jam_akhir' => '11:00', 'status' => 'closed',
            ]);
            $this->assertSame(1, Artisan::call('hourly:activate', ['venue' => $venue, '--apply' => true]));
            DB::table('jadwal_lapangan')->where('id', $legacy)->update(['status' => 'ready']);
            $this->assertSame(0, Artisan::call('hourly:activate', ['venue' => $venue]));
            $this->assertDatabaseHas('lapangan', ['id' => $venue, 'hourly_booking_enabled' => false]);
            $this->assertSame(0, Artisan::call('hourly:activate', ['venue' => $venue, '--apply' => true]));
            $this->assertDatabaseHas('lapangan', ['id' => $venue, 'hourly_booking_enabled' => true]);
            $events = collect(app(Schedule::class)->events());
            $this->assertSame('* * * * *', $events->first(fn ($event) => str_contains($event->command, 'hourly:maintain expiry'))->expression);
            $this->assertSame('*/15 * * * *', $events->first(fn ($event) => str_contains($event->command, 'hourly:maintain slots'))->expression);

            $this->assertTrue($maintenance->needsSlots());
            $this->assertSame(0, Artisan::call('hourly:maintain', ['task' => 'slots']));
            $this->assertFalse($maintenance->needsSlots());
            $slot = DB::table('schedule_slots')->where('venue_court_id', $court)
                ->where('starts_at', '2030-05-01 10:00:00')->value('id');
            $booking = app(HourlyBooking::class)->reserve($venue, [$slot], 'Fixture', '0800000000', (string) Str::uuid(), str_repeat('x', 40));
            $this->assertSame(0, Artisan::call('hourly:maintain', ['task' => 'expiry']));

            CarbonImmutable::setTestNow(CarbonImmutable::parse('2030-05-01 09:00', 'Asia/Jakarta'));
            $this->assertTrue($maintenance->health()['expiryLate']);
            $this->assertSame(1, $maintenance->health()['backlog']);
            $this->actingAs($admin)->get('/home')->assertOk()->assertSee('Periksa proses kedaluwarsa');
            $this->actingAs($keeper)->get('/home')->assertOk()->assertDontSee('Proses booking otomatis');

            $this->assertSame(0, Artisan::call('hourly:maintain', ['task' => 'expiry']));
            $this->assertDatabaseHas('konfirmasi_bookings', ['id' => $booking->id, 'status' => 'expired']);
            $this->assertDatabaseMissing('active_slot_reservations', ['booking_id' => $booking->id]);
            $this->assertDatabaseHas('booking_slots', ['booking_id' => $booking->id, 'slot_id' => $slot]);
            $this->assertFalse($maintenance->health()['expiryLate']);
            $this->assertSame(0, $maintenance->health()['backlog']);
            $this->assertSame(1, $maintenance->health()['expiry']->processed_count);

            $closure = $ops->closeCourt($keeper, $court, '2030-05-03', '10:00', '11:00', 'Maintenance');
            CarbonImmutable::setTestNow(CarbonImmutable::parse('2030-05-03 00:15', 'Asia/Jakarta'));
            $this->assertTrue($maintenance->needsSlots());
            Log::spy();
            app()->instance(CourtOperations::class, new class {
                public function generate(int $venue): int
                {
                    throw new \RuntimeException('Fixture failure');
                }
            });
            try {
                $this->assertSame(1, Artisan::call('hourly:maintain', ['task' => 'slots']));
                $this->assertSame('venue_failure', $maintenance->health()['failure']->error_code);
                $this->assertTrue($maintenance->needsSlots());
            } finally {
                app()->instance(CourtOperations::class, $ops);
            }
            $this->assertSame(0, Artisan::call('hourly:maintain', ['task' => 'slots']));
            $this->assertDatabaseHas('schedule_slots', ['venue_court_id' => $court, 'starts_at' => '2030-05-16 10:00:00']);
            $this->assertDatabaseHas('court_closures', ['id' => $closure, 'reopened_at' => null]);
            $this->assertFalse($maintenance->needsSlots());
            $this->assertSame(0, Artisan::call('hourly:maintain', ['task' => 'slots']));
            $this->assertSame(0, $maintenance->health()['slots']->processed_count);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    private function user(string $role): User
    {
        return User::create([
            'name' => 'Scheduler', 'last_name' => 'Fixture', 'email' => Str::uuid().'@example.test',
            'password' => 'test-only', 'role' => $role,
        ]);
    }
}

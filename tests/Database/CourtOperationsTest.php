<?php

namespace Tests\Database;

use App\Models\User;
use App\Services\CourtOperations;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CourtOperationsTest extends TestCase
{
    public function test_operating_rules_generation_closures_access_and_reservation_conflicts(): void
    {
        (require base_path('database/staging/2026_11_04_000001_hourly_booking_schema.php'))->up();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2030-01-01 09:59:00', 'Asia/Jakarta'));
        DB::beginTransaction();
        try {
            $admin = $this->user('admin');
            $owner = $this->user('penjaga');
            $other = $this->user('penjaga');
            $venue = DB::table('lapangan')->insertGetId(['nama_lapangan' => 'Operations fixture', 'alamat' => 'Test', 'user_id' => $owner->id]);
            $court = DB::table('venue_courts')->insertGetId(['lapangan_id' => $venue, 'name' => 'Court A', 'is_active' => true]);
            DB::table('venue_courts')->insert(['lapangan_id' => $venue, 'name' => 'Court B', 'is_active' => true]);
            DB::table('venue_courts')->insert(['lapangan_id' => $venue, 'name' => 'Inactive court', 'is_active' => false]);
            $service = new CourtOperations();
            $this->assertSame(0, $service->generate($venue));
            $this->denied(fn () => $service->configure($owner, $venue, '08:00', '12:00', 14, 'Test'));
            $service->configure($admin, $venue, '08:00', '12:00', 14, 'Initial setup');
            $this->assertFalse((bool) DB::table('lapangan')->find($venue)->hourly_booking_enabled);
            DB::table('lapangan')->where('id', $venue)->update(['hourly_booking_enabled' => true]);
            $this->assertSame(112, $service->generate($venue));
            $this->assertSame(0, $service->generate($venue));
            $this->assertSame(8, count($service->availability($venue, '2030-01-01')));
            $this->assertSame([], $service->availability($venue, '2029-12-31'));
            $this->assertSame([], $service->availability($venue, '2030-01-15'));
            $this->assertCount(8, $service->availability($venue, '2030-01-14'));
            $atTen = fn () => collect($service->availability($venue, '2030-01-01'))->first(fn ($s) => $s['court_id'] === $court && substr($s['starts_at'], 11, 5) === '10:00');
            $this->assertTrue($atTen()['available']);
            CarbonImmutable::setTestNow(CarbonImmutable::parse('2030-01-01 10:00:00', 'Asia/Jakarta'));
            $this->assertFalse($atTen()['available']);
            $this->assertSame('Tidak tersedia', $atTen()['label']);
            CarbonImmutable::setTestNow(CarbonImmutable::parse('2030-01-01 09:59:00', 'Asia/Jakarta'));

            $this->denied(fn () => $service->closeCourt($other, $court, '2030-01-01', '10:00', '11:00', 'Foreign'));
            $closure = $service->closeCourt($owner, $court, '2030-01-01', '10:00', '11:00', 'Maintenance');
            $this->assertSame('closed', $atTen()['reason']);
            $this->assertSame(0, $service->generate($venue));
            $this->assertSame('closed', $atTen()['reason']);
            $overlap = $service->closeCourt($admin, $court, '2030-01-01', '10:00', '12:00', 'Admin event');
            $this->denied(fn () => $service->reopen($owner, $overlap, 'Not permitted'));
            $service->reopen($owner, $closure, 'Maintenance complete');
            $this->assertSame('closed', $atTen()['reason']);
            $service->reopen($admin, $overlap, 'Event ended');
            $this->assertTrue($atTen()['available']);

            $this->denied(fn () => $service->exception($other, $venue, '2030-01-02', null, null, 'Foreign'));
            $service->exception($owner, $venue, '2030-01-02', null, null, 'Holiday');
            $this->assertCount(8, $service->availability($venue, '2030-01-02'));
            $this->assertSame(0, collect($service->availability($venue, '2030-01-02'))->where('available', true)->count());
            $this->assertSame(0, $service->generate($venue));
            $service->exception($owner, $venue, '2030-01-02', '10:00', '12:00', 'Special hours');
            $this->assertSame(4, collect($service->availability($venue, '2030-01-02'))->where('available', true)->count());
            $service->exception($admin, $venue, '2030-01-02', null, null, 'Admin override');
            $this->denied(fn () => $service->exception($owner, $venue, '2030-01-02', '08:00', '12:00', 'Override admin'));
            $service->exception($admin, $venue, '2030-01-03', null, null, 'Admin holiday');
            $this->denied(fn () => $service->exception($owner, $venue, '2030-01-03', '08:00', '12:00', 'Override'));

            $slot = DB::table('schedule_slots')->where('venue_court_id', $court)->where('starts_at', '2030-01-01 10:00:00')->first();
            $booking = DB::table('konfirmasi_bookings')->insertGetId([
                'is_legacy' => false, 'venue_court_id' => $court, 'source' => 'website',
                'nama' => 'Test', 'no_whatsapp' => '0800000000', 'durasi' => 1, 'status' => 'pending',
                'starts_at' => $slot->starts_at, 'ends_at' => $slot->ends_at, 'expires_at' => '2030-01-01 10:00:00',
            ]);
            DB::table('booking_slots')->insert(['booking_id' => $booking, 'slot_id' => $slot->id, 'venue_court_id' => $court]);
            DB::table('active_slot_reservations')->insert(['booking_id' => $booking, 'slot_id' => $slot->id, 'venue_court_id' => $court]);
            $this->assertSame('reserved', $atTen()['reason']);
            $this->invalid(fn () => $service->closeCourt($owner, $court, '2030-01-01', '10:00', '11:00', 'Conflict'));
            $this->invalid(fn () => $service->configure($admin, $venue, '11:00', '12:00', 14, 'Conflict'));
            $this->assertSame('08:00:00', DB::table('lapangan')->find($venue)->regular_opens_at);
            $this->invalid(fn () => $service->exception($owner, $venue, '2030-01-01', null, null, 'Conflict'));
            $this->assertFalse(DB::table('operating_exceptions')->where('lapangan_id', $venue)->where('date', '2030-01-01')->exists());
            $service->generate($venue);
            $this->assertSame(1, DB::table('active_slot_reservations')->where('slot_id', $slot->id)->count());
            $this->invalid(fn () => $service->configure($admin, $venue, '08:30', '12:00', 14, 'Invalid'));
            $this->invalid(fn () => $service->configure($admin, $venue, '22:00', '08:00', 14, 'Invalid'));
            $this->invalid(fn () => $service->configure($admin, $venue, '08:00', '12:00', 0, 'Invalid'));
            $this->invalid(fn () => $service->exception($owner, $venue, '2030-02-30', null, null, 'Invalid'));

            $service->exception($owner, $venue, '2030-01-15', null, null, 'Future holiday');
            CarbonImmutable::setTestNow(CarbonImmutable::parse('2030-01-02 00:00', 'Asia/Jakarta'));
            $this->assertSame(8, $service->generate($venue));
            $this->assertCount(8, $service->availability($venue, '2030-01-15'));
            $this->assertSame(0, collect($service->availability($venue, '2030-01-15'))->where('available', true)->count());
            $this->assertSame([], $service->availability($venue, '2030-01-01'));
            $this->assertGreaterThan(0, DB::table('activity_logs')->where('lapangan_id', $venue)->count());
        } finally {
            DB::rollBack();
            CarbonImmutable::setTestNow();
        }
    }

    private function user(string $role): User
    {
        return User::create(['name' => 'Operations', 'last_name' => 'Test', 'email' => bin2hex(random_bytes(8)).'@example.test', 'password' => 'test-only', 'role' => $role]);
    }

    private function denied(callable $action): void
    {
        try { $action(); $this->fail('Expected authorization failure'); }
        catch (HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
    }

    private function invalid(callable $action): void
    {
        try { $action(); $this->fail('Expected validation conflict'); }
        catch (ValidationException $e) { $this->assertNotEmpty($e->errors()); }
    }
}

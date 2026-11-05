<?php

namespace Tests\Database;

use App\Models\User;
use App\Services\CourtOperations;
use App\Services\HourlyBooking;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class HourlyBookingTest extends TestCase
{
    public function test_booking_lifecycle_and_parallel_requests(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2030-02-01 08:00', 'Asia/Jakarta'));
        try {
            $owner = $this->user('penjaga');
            $foreign = $this->user('penjaga');
            $admin = $this->user('admin');
            $venue = DB::table('lapangan')->insertGetId([
                'nama_lapangan' => 'Lifecycle fixture', 'alamat' => 'Test', 'user_id' => $owner->id,
                'regular_opens_at' => '08:00', 'regular_closes_at' => '22:00', 'hourly_booking_enabled' => true,
            ]);
            $court = DB::table('venue_courts')->insertGetId(['lapangan_id' => $venue, 'name' => 'Lifecycle A', 'is_active' => true]);
            $ops = app(CourtOperations::class);
            $ops->generate($venue);
            $slot = fn ($hour) => (int) DB::table('schedule_slots')->where('venue_court_id', $court)->where('starts_at', '2030-02-01 '.$hour.':00:00')->value('id');
            $service = app(HourlyBooking::class);
            $scope = str_repeat('session-scope', 4);
            $reserve = fn ($ids, $key) => $service->reserve($venue, $ids, 'Fixture customer', '0800000000', str_pad($key, 24, 'x'), $scope);
            $booking = $reserve([$slot('10'), $slot('11')], 'initial');
            $this->assertSame('pending', $booking->status);
            $this->assertSame(2, $booking->durasi);
            $this->assertSame('2030-02-01 09:00:00', $booking->expires_at);
            $this->assertSame(2, DB::table('active_slot_reservations')->where('booking_id', $booking->id)->count());
            $this->assertSame($booking->id, $reserve([$slot('11'), $slot('10')], 'initial')->id);
            $this->invalid(fn () => $reserve([$slot('12')], 'initial'));
            $before = DB::table('konfirmasi_bookings')->count();
            $this->invalid(fn () => $reserve([$slot('09'), $slot('10')], 'partial'));
            $this->assertSame($before, DB::table('konfirmasi_bookings')->count());
            $this->assertFalse(DB::table('active_slot_reservations')->where('slot_id', $slot('09'))->exists());
            $this->invalid(fn () => $reserve([$slot('12'), $slot('14')], 'nonconsecutive'));
            $this->invalid(fn () => $reserve([$slot('08')], 'started'));
            try { $service->confirm($foreign, $booking->id); $this->fail('Foreign confirmation accepted'); }
            catch (HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
            $this->assertSame('confirmed', $service->confirm($owner, $booking->id)->status);
            CarbonImmutable::setTestNow(CarbonImmutable::parse('2030-02-01 09:01', 'Asia/Jakarta'));
            $this->assertSame(0, $service->expireForVenue($venue));
            $this->assertSame('confirmed', DB::table('konfirmasi_bookings')->find($booking->id)->status);
            $this->assertSame('canceled', $service->cancel($owner, $booking->id, 'Customer request')->status);
            $this->assertSame(2, DB::table('booking_slots')->where('booking_id', $booking->id)->count());
            $this->assertSame(0, DB::table('active_slot_reservations')->where('booking_id', $booking->id)->count());
            $closure = $ops->closeCourt($owner, $court, '2030-02-01', '10:00', '11:00', 'Maintenance after cancellation');
            $this->invalid(fn () => $reserve([$slot('10')], 'closed'));
            $ops->reopen($owner, $closure, 'Maintenance complete');

            CarbonImmutable::setTestNow(CarbonImmutable::parse('2030-02-01 09:30', 'Asia/Jakarta'));
            $short = $reserve([$slot('10'), $slot('11')], 'short');
            $this->assertSame('2030-02-01 10:00:00', $short->expires_at);
            CarbonImmutable::setTestNow(CarbonImmutable::parse('2030-02-01 10:00', 'Asia/Jakarta'));
            $this->invalid(fn () => $service->confirm($owner, $short->id));
            $this->assertSame('expired', DB::table('konfirmasi_bookings')->find($short->id)->status);
            $this->assertSame('expired', $reserve([$slot('10'), $slot('11')], 'short')->status);
            $this->assertSame(0, $service->expireForVenue($venue));
            $states = collect($ops->availability($venue, '2030-02-01'))->keyBy('id');
            $this->assertFalse($states[$slot('10')]['available']);
            $this->assertTrue($states[$slot('11')]['available']);

            $offline = $service->reserve($venue, [$slot('11')], 'Offline fixture', '0800000000', str_repeat('o', 24), $scope, 'whatsapp', $owner, true);
            $this->assertSame('confirmed', $offline->status);
            $this->assertSame($owner->id, $offline->confirmed_by);
            CarbonImmutable::setTestNow(CarbonImmutable::parse('2030-02-01 11:00', 'Asia/Jakarta'));
            $this->invalid(fn () => $service->cancel($admin, $offline->id, 'Too late'));

            $start = microtime(true) + 1;
            $base = ['action' => 'reserve', 'venue' => $venue, 'slot' => $slot('12'), 'now' => '2030-02-01 10:00', 'start' => $start];
            $results = $this->parallel([$base + ['key' => str_repeat('a', 24)], $base + ['key' => str_repeat('b', 24)]]);
            $this->assertSame(1, count(array_filter($results, fn ($r) => $r['ok'])));
            $this->assertSame(1, count(array_filter($results, fn ($r) => $r['conflict'] ?? false)));
            $this->assertSame(1, DB::table('active_slot_reservations')->where('slot_id', $slot('12'))->count());
            $same = ['action' => 'reserve', 'venue' => $venue, 'slot' => $slot('13'), 'now' => '2030-02-01 10:00', 'start' => microtime(true) + 1, 'key' => str_repeat('same-key', 3)];
            $retries = $this->parallel([$same, $same]);
            $this->assertTrue($retries[0]['ok']);
            $this->assertTrue($retries[1]['ok']);
            $this->assertSame($retries[0]['id'], $retries[1]['id']);

            $raceBooking = DB::table('active_slot_reservations')->where('slot_id', $slot('12'))->value('booking_id');
            $base = ['venue' => $venue, 'booking' => $raceBooking, 'actor' => $owner->id, 'now' => '2030-02-01 11:00', 'start' => microtime(true) + 1];
            $results = $this->parallel([$base + ['action' => 'confirm'], $base + ['action' => 'expire']]);
            $this->assertSame('expired', DB::table('konfirmasi_bookings')->find($raceBooking)->status);
            $this->assertFalse(DB::table('active_slot_reservations')->where('slot_id', $slot('12'))->exists());
            $this->assertSame(1, DB::table('activity_logs')->where('subject_id', $raceBooking)->where('action', 'booking_expired')->count());
            $this->assertSame(1, count(array_filter($results, fn ($r) => $r['conflict'] ?? false)));
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    private function parallel(array $requests): array
    {
        $workers = [];
        foreach ($requests as $request) {
            $process = proc_open([PHP_BINARY, base_path('tests/Support/booking-worker.php'), base64_encode(json_encode($request))],
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
            $this->assertIsResource($process);
            fclose($pipes[0]);
            $workers[] = [$process, $pipes];
        }
        $results = [];
        foreach ($workers as [$process, $pipes]) {
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            $this->assertSame(0, proc_close($process), 'Worker failed: '.$error);
            $results[] = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        }
        return $results;
    }

    private function user(string $role): User
    {
        return User::create(['name' => 'Lifecycle', 'last_name' => 'Fixture', 'email' => bin2hex(random_bytes(8)).'@example.test', 'password' => 'test-only', 'role' => $role]);
    }

    private function invalid(callable $action): void
    {
        try { $action(); $this->fail('Expected validation rejection'); }
        catch (ValidationException $e) { $this->assertNotEmpty($e->errors()); }
    }
}

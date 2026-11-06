<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class HourlyMaintenance
{
    public const EXPIRY = 'expiry';
    public const SLOTS = 'slots';

    public function ready(): bool
    {
        return Schema::hasTable('operational_task_runs') && Schema::hasColumn('lapangan', 'hourly_booking_enabled');
    }

    public function needsSlots(): bool
    {
        return $this->ready() && !$this->runs(self::SLOTS)->where('status', 'succeeded')
            ->where('finished_at', '>=', $this->now()->startOfDay())->exists();
    }

    public function run(string $task): bool
    {
        $run = DB::table('operational_task_runs')->insertGetId([
            'task' => $task, 'started_at' => $this->now(), 'status' => 'running',
        ]);

        $processed = 0;
        $failure = null;
        try {
            $venues = $task === self::EXPIRY
                ? $this->due()->select('c.lapangan_id')->distinct()->orderBy('c.lapangan_id')->pluck('c.lapangan_id')
                : DB::table('lapangan')->where('hourly_booking_enabled', true)->orderBy('id')->pluck('id');

            foreach ($venues as $venueId) {
                try {
                    $processed += $task === self::EXPIRY
                        ? app(HourlyBooking::class)->expireForVenue($venueId)
                        : app(CourtOperations::class)->generate($venueId);
                } catch (Throwable $error) {
                    $failure = 'venue_failure';
                    Log::error('Hourly maintenance failed for venue', [
                        'task' => $task, 'venue_id' => $venueId, 'exception_type' => $error::class,
                    ]);
                }
            }
        } catch (Throwable $error) {
            $failure = 'discovery_failure';
            Log::error('Hourly maintenance failed to discover venues', [
                'task' => $task, 'exception_type' => $error::class,
            ]);
        }

        DB::table('operational_task_runs')->where('id', $run)->update([
            'finished_at' => $this->now(), 'status' => $failure ? 'failed' : 'succeeded',
            'processed_count' => $processed, 'error_code' => $failure,
        ]);

        return $failure === null;
    }

    public function health(): array
    {
        $expiry = $this->runs(self::EXPIRY)->where('status', 'succeeded')->orderByDesc('id')->first();
        $slots = $this->runs(self::SLOTS)->where('status', 'succeeded')->orderByDesc('id')->first();
        $failure = DB::table('operational_task_runs')->where('status', 'failed')->orderByDesc('id')->first();
        $backlog = $this->due()->count();

        return [
            'expiry' => $expiry, 'slots' => $slots, 'failure' => $failure, 'backlog' => $backlog,
            'expiryLate' => !$expiry || CarbonImmutable::parse($expiry->finished_at, 'Asia/Jakarta')->lt($this->now()->subMinutes(3)),
            'slotsLate' => !$slots || $slots->finished_at < $this->now()->startOfDay()->format('Y-m-d H:i:s'),
        ];
    }

    private function due()
    {
        return DB::table('konfirmasi_bookings as b')->join('venue_courts as c', 'c.id', '=', 'b.venue_court_id')
            ->where('b.is_legacy', false)->where('b.status', 'pending')->where('b.expires_at', '<=', $this->now());
    }

    private function runs(string $task)
    {
        return DB::table('operational_task_runs')->where('task', $task);
    }

    private function now(): CarbonImmutable
    {
        return CarbonImmutable::now('Asia/Jakarta');
    }
}

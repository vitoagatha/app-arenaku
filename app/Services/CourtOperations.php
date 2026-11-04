<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * All writers, including the upcoming booking service, must lock lapangan first.
 * These operations require the hourly schema and do not activate legacy venues.
 */
final class CourtOperations
{
    private function now(): CarbonImmutable
    {
        return CarbonImmutable::now('Asia/Jakarta');
    }

    private function venue(int $id, ?User $actor = null): object
    {
        $venue = DB::table('lapangan')->where('id', $id)->lockForUpdate()->first();
        abort_unless($venue, 404);
        if ($actor) {
            abort_unless($actor->role === 'admin' || ($actor->role === 'penjaga' && (int) $venue->user_id === (int) $actor->id), 403);
        }
        return $venue;
    }

    private function hours(?string $open, ?string $close): void
    {
        Validator::make(['open' => $open, 'close' => $close], [
            'open' => ['required', 'regex:/^(?:[01][0-9]|2[0-3]):00$/'],
            'close' => ['required', 'regex:/^(?:[01][0-9]|2[0-3]):00$/', 'after:open'],
        ])->validate();
    }

    private function date(string $date): void
    {
        Validator::make(['date' => $date], ['date' => 'required|date_format:Y-m-d'])->validate();
    }

    private function log(int $venue, User $actor, string $type, int $id, string $action, string $reason, array $changes): void
    {
        DB::table('activity_logs')->insert([
            'lapangan_id' => $venue, 'actor_id' => $actor->id, 'subject_type' => $type,
            'subject_id' => $id, 'action' => $action, 'reason' => $reason,
            'changes' => json_encode($changes, JSON_THROW_ON_ERROR), 'occurred_at' => $this->now(),
        ]);
    }

    public function configure(User $actor, int $venueId, string $open, string $close, int $days, string $reason): void
    {
        abort_unless($actor->role === 'admin', 403);
        $this->hours($open, $close);
        Validator::make(compact('days', 'reason'), ['days' => 'integer|min:1|max:365', 'reason' => 'required|string|max:1000'])->validate();
        DB::transaction(function () use ($actor, $venueId, $open, $close, $days, $reason) {
            $old = $this->venue($venueId, $actor);
            $values = ['regular_opens_at' => $open, 'regular_closes_at' => $close, 'booking_window_days' => $days];
            DB::table('lapangan')->where('id', $venueId)->update($values);
            $this->assertReservationsFit($venueId);
            $this->log($venueId, $actor, 'venue', $venueId, 'regular_hours_updated', $reason, [
                'before' => ['open' => $old->regular_opens_at, 'close' => $old->regular_closes_at, 'days' => $old->booking_window_days],
                'after' => $values,
            ]);
        });
    }

    public function exception(User $actor, int $venueId, string $date, ?string $open, ?string $close, string $reason): void
    {
        $this->date($date);
        Validator::make(compact('reason'), ['reason' => 'required|string|max:1000'])->validate();
        if ($open !== null || $close !== null) {
            $this->hours($open, $close);
        }
        DB::transaction(function () use ($actor, $venueId, $date, $open, $close, $reason) {
            $this->venue($venueId, $actor);
            $existing = DB::table('operating_exceptions')->where('lapangan_id', $venueId)->where('date', $date)->first();
            abort_if($existing && (int) $existing->created_by !== (int) $actor->id && $actor->role !== 'admin', 403);
            abort_if($existing && $actor->role !== 'admin' && DB::table('users')->where('id', $existing->updated_by)->where('role', 'admin')->exists(), 403);
            $values = ['is_closed' => $open === null, 'opens_at' => $open, 'closes_at' => $close, 'reason' => $reason, 'updated_by' => $actor->id, 'updated_at' => $this->now()];
            if ($existing) {
                DB::table('operating_exceptions')->where('id', $existing->id)->update($values);
                $id = $existing->id;
            } else {
                $id = DB::table('operating_exceptions')->insertGetId($values + ['lapangan_id' => $venueId, 'date' => $date, 'created_by' => $actor->id, 'created_at' => $this->now()]);
            }
            $this->assertReservationsFit($venueId);
            $this->log($venueId, $actor, 'exception', $id, 'exception_saved', $reason, ['before' => $existing, 'after' => $values]);
        });
    }

    private function effectiveHours(object $venue, string $date): ?array
    {
        $special = DB::table('operating_exceptions')->where('lapangan_id', $venue->id)->where('date', $date)->first();
        if ($special && $special->is_closed) {
            return null;
        }
        $open = $special ? $special->opens_at : $venue->regular_opens_at;
        $close = $special ? $special->closes_at : $venue->regular_closes_at;
        return $open && $close ? [substr($open, 0, 5), substr($close, 0, 5)] : null;
    }

    private function reservations(int $venueId)
    {
        // Conservatively preserve allocated slots, including stale pending; lifecycle release is stage 4.
        return DB::table('active_slot_reservations as r')
            ->join('schedule_slots as s', 's.id', '=', 'r.slot_id')
            ->join('venue_courts as c', 'c.id', '=', 's.venue_court_id')
            ->where('c.lapangan_id', $venueId)->where('s.ends_at', '>', $this->now()->format('Y-m-d H:i:s'));
    }

    private function assertReservationsFit(int $venueId): void
    {
        $venue = DB::table('lapangan')->find($venueId);
        foreach ($this->reservations($venueId)->select('s.starts_at', 's.ends_at')->get() as $slot) {
            $hours = $this->effectiveHours($venue, substr($slot->starts_at, 0, 10));
            if (!$hours || substr($slot->starts_at, 11, 5) < $hours[0] || substr($slot->ends_at, 11, 5) > $hours[1]) {
                throw ValidationException::withMessages(['hours' => 'Perubahan bertabrakan dengan booking aktif. Selesaikan booking terlebih dahulu.']);
            }
        }
    }

    public function closeCourt(User $actor, int $courtId, string $date, string $open, string $close, string $reason): int
    {
        $this->date($date);
        $this->hours($open, $close);
        Validator::make(compact('reason'), ['reason' => 'required|string|max:1000'])->validate();
        return DB::transaction(function () use ($actor, $courtId, $date, $open, $close, $reason) {
            $court = DB::table('venue_courts')->find($courtId);
            abort_unless($court, 404);
            $this->venue($court->lapangan_id, $actor);
            $start = "$date $open:00";
            $end = "$date $close:00";
            if ($this->reservations($court->lapangan_id)->where('s.venue_court_id', $courtId)->where('s.starts_at', '<', $end)->where('s.ends_at', '>', $start)->exists()) {
                throw ValidationException::withMessages(['closure' => 'Penutupan bertabrakan dengan booking aktif.']);
            }
            $id = DB::table('court_closures')->insertGetId(['venue_court_id' => $courtId, 'starts_at' => $start, 'ends_at' => $end, 'reason' => $reason, 'created_by' => $actor->id, 'created_at' => $this->now(), 'updated_at' => $this->now()]);
            $this->log($court->lapangan_id, $actor, 'closure', $id, 'court_closed', $reason, ['start' => $start, 'end' => $end]);
            return $id;
        });
    }

    public function reopen(User $actor, int $closureId, string $reason): void
    {
        Validator::make(compact('reason'), ['reason' => 'required|string|max:1000'])->validate();
        DB::transaction(function () use ($actor, $closureId, $reason) {
            $closure = DB::table('court_closures')->find($closureId);
            abort_unless($closure, 404);
            $court = DB::table('venue_courts')->find($closure->venue_court_id);
            $this->venue($court->lapangan_id, $actor);
            $closure = DB::table('court_closures')->where('id', $closureId)->lockForUpdate()->first();
            abort_unless($actor->role === 'admin' || (int) $closure->created_by === (int) $actor->id, 403);
            if ($closure->reopened_at) {
                return;
            }
            DB::table('court_closures')->where('id', $closureId)->update(['reopened_at' => $this->now(), 'reopened_by' => $actor->id, 'reopen_reason' => $reason, 'updated_at' => $this->now()]);
            $this->log($court->lapangan_id, $actor, 'closure', $closureId, 'court_reopened', $reason, []);
        });
    }

    public function generate(int $venueId): int
    {
        return DB::transaction(function () use ($venueId) {
            $venue = $this->venue($venueId);
            if (!$venue->hourly_booking_enabled || !$venue->regular_opens_at || !$venue->regular_closes_at) {
                return 0;
            }
            $created = 0;
            $today = $this->now()->startOfDay();
            $courts = DB::table('venue_courts')->where('lapangan_id', $venueId)->where('is_active', true)->pluck('id');
            for ($day = 0; $day < $venue->booking_window_days; $day++) {
                $date = $today->addDays($day)->format('Y-m-d');
                $hours = $this->effectiveHours($venue, $date);
                if (!$hours) {
                    // Keep the regular hourly grid visible on a full-day holiday.
                    $hours = [substr($venue->regular_opens_at, 0, 5), substr($venue->regular_closes_at, 0, 5)];
                }
                $start = CarbonImmutable::parse("$date {$hours[0]}", 'Asia/Jakarta');
                $end = CarbonImmutable::parse("$date {$hours[1]}", 'Asia/Jakarta');
                foreach ($courts as $court) {
                    for ($time = $start; $time->lessThan($end); $time = $time->addHour()) {
                        if (!DB::table('schedule_slots')->where('venue_court_id', $court)->where('starts_at', $time->format('Y-m-d H:i:s'))->exists()) {
                            $created += DB::table('schedule_slots')->insert(['venue_court_id' => $court, 'starts_at' => $time, 'ends_at' => $time->addHour(), 'created_at' => $this->now(), 'updated_at' => $this->now()]) ? 1 : 0;
                        }
                    }
                }
            }
            return $created;
        });
    }

    public function availability(int $venueId, string $date): array
    {
        $this->date($date);
        return DB::transaction(function () use ($venueId, $date) {
            $venue = $this->venue($venueId);
            $today = $this->now()->startOfDay();
            $chosen = CarbonImmutable::parse($date, 'Asia/Jakarta');
            if (!$venue->hourly_booking_enabled || $chosen->lessThan($today) || $chosen->greaterThanOrEqualTo($today->addDays($venue->booking_window_days))) {
                return [];
            }
            $hours = $this->effectiveHours($venue, $date);
            $closures = DB::table('court_closures as x')->join('venue_courts as c', 'c.id', '=', 'x.venue_court_id')
                ->where('c.lapangan_id', $venueId)->whereNull('x.reopened_at')->whereDate('x.starts_at', $date)->select('x.*')->get();
            $reserved = DB::table('active_slot_reservations as r')->join('schedule_slots as s', 's.id', '=', 'r.slot_id')
                ->join('venue_courts as c', 'c.id', '=', 's.venue_court_id')->where('c.lapangan_id', $venueId)
                ->whereDate('s.starts_at', $date)->pluck('r.slot_id')->flip();
            return DB::table('schedule_slots as s')->join('venue_courts as c', 'c.id', '=', 's.venue_court_id')
                ->where('c.lapangan_id', $venueId)->where('c.is_active', true)->whereDate('s.starts_at', $date)
                ->orderBy('c.id')->orderBy('s.starts_at')->select('s.*', 'c.name as court_name')->get()
                ->map(function ($slot) use ($hours, $closures, $reserved) {
                    $reason = null;
                    if (CarbonImmutable::parse($slot->starts_at, 'Asia/Jakarta')->lessThanOrEqualTo($this->now())) {
                        $reason = 'started';
                    } elseif (!$hours || substr($slot->starts_at, 11, 5) < $hours[0] || substr($slot->ends_at, 11, 5) > $hours[1]) {
                        $reason = 'outside_hours';
                    } elseif ($closures->contains(fn ($closure) => $closure->venue_court_id === $slot->venue_court_id && $closure->starts_at < $slot->ends_at && $closure->ends_at > $slot->starts_at)) {
                        $reason = 'closed';
                    } elseif ($reserved->has($slot->id)) {
                        $reason = 'reserved';
                    }
                    return ['id' => $slot->id, 'court_id' => $slot->venue_court_id, 'court_name' => $slot->court_name, 'starts_at' => $slot->starts_at, 'ends_at' => $slot->ends_at, 'available' => $reason === null, 'reason' => $reason, 'label' => $reason === null ? 'Tersedia' : ($reason === 'reserved' ? 'Dipesan' : 'Tidak tersedia')];
                })->all();
        });
    }
}

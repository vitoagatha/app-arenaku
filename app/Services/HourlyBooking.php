<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class HourlyBooking
{
    private function now(): CarbonImmutable
    {
        return CarbonImmutable::now('Asia/Jakarta');
    }

    private function lockVenue(int $id, ?User $actor = null): object
    {
        $venue = DB::table('lapangan')->where('id', $id)->lockForUpdate()->first();
        abort_unless($venue, 404);
        if ($actor) {
            abort_unless($actor->role === 'admin' || ($actor->role === 'penjaga' && (int) $venue->user_id === (int) $actor->id), 403);
        }
        return $venue;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['booking' => $message]);
    }

    private function event(int $venue, int $booking, string $action, ?User $actor, ?string $reason = null): void
    {
        DB::table('activity_logs')->insert([
            'lapangan_id' => $venue, 'actor_id' => $actor?->id, 'subject_type' => 'booking',
            'subject_id' => $booking, 'action' => $action, 'reason' => $reason, 'occurred_at' => $this->now(),
        ]);
    }

    /** Caller supplies a server-owned, unguessable session/request scope, never a customer-provided identity. */
    public function reserve(int $venueId, array $slotIds, string $name, string $phone, string $key, string $scope, string $source = 'website', ?User $actor = null, bool $confirmed = false): object
    {
        Validator::make(compact('slotIds', 'name', 'phone', 'key', 'scope', 'source'), [
            'slotIds' => 'required|array|min:1|max:24', 'slotIds.*' => 'required|integer|min:1|distinct',
            'name' => 'required|string|max:255', 'phone' => 'required|string|max:255',
            'key' => 'required|string|min:16|max:200', 'scope' => 'required|string|min:32|max:200',
            'source' => 'required|in:website,walk_in,whatsapp',
        ])->validate();
        abort_unless($actor || ($source === 'website' && !$confirmed), 403);
        $slotIds = array_map('intval', $slotIds);
        sort($slotIds);
        $hash = hash('sha256', $venueId.'|'.$scope.'|'.$key);
        return DB::transaction(function () use ($venueId, $slotIds, $name, $phone, $hash, $source, $actor, $confirmed) {
            $venue = $this->lockVenue($venueId, $actor);
            $existing = DB::table('konfirmasi_bookings')->where('idempotency_key_hash', $hash)->first();
            if ($existing) {
                $oldIds = DB::table('booking_slots')->where('booking_id', $existing->id)->orderBy('slot_id')->pluck('slot_id')->map(fn ($id) => (int) $id)->all();
                if ($oldIds !== $slotIds || $existing->nama !== $name || $existing->no_whatsapp !== $phone || $existing->source !== $source) {
                    $this->fail('Kunci pengiriman sudah digunakan untuk pilihan yang berbeda.');
                }
                $this->expireLocked($venueId);
                return DB::table('konfirmasi_bookings')->find($existing->id);
            }
            if (!$venue->hourly_booking_enabled) {
                $this->fail('Booking per jam belum aktif.');
            }
            $this->expireLocked($venueId);
            $slots = DB::table('schedule_slots')->whereIn('id', $slotIds)->orderBy('starts_at')->orderBy('id')->lockForUpdate()->get();
            if ($slots->count() !== count($slotIds)) {
                $this->fail('Slot tidak ditemukan.');
            }
            $first = $slots->first();
            $court = DB::table('venue_courts')->find($first->venue_court_id);
            if (!$court || (int) $court->lapangan_id !== $venueId || !$court->is_active) {
                $this->fail('Court tidak tersedia.');
            }
            $date = substr($first->starts_at, 0, 10);
            $available = collect(app(CourtOperations::class)->availability($venueId, $date))->keyBy('id');
            $end = $first->starts_at;
            foreach ($slots as $slot) {
                if ($slot->venue_court_id !== $first->venue_court_id || substr($slot->starts_at, 0, 10) !== $date
                    || $slot->starts_at !== $end || !($available->get($slot->id)['available'] ?? false)) {
                    $this->fail('Pilih slot berurutan pada satu court dan tanggal yang masih tersedia.');
                }
                $end = $slot->ends_at;
            }
            $now = $this->now();
            if ($first->starts_at <= $now->format('Y-m-d H:i:s')) {
                $this->fail('Waktu mulai slot sudah tiba. Silakan pilih jadwal lain.');
            }
            $expires = $now->addMinutes(60)->min(CarbonImmutable::parse($first->starts_at, 'Asia/Jakarta'));
            $id = DB::table('konfirmasi_bookings')->insertGetId([
                'jadwal_lapangan_id' => null, 'is_legacy' => false, 'venue_court_id' => $court->id,
                'nama' => $name, 'no_whatsapp' => $phone, 'durasi' => $slots->count(),
                'source' => $source, 'created_by' => $actor?->id, 'status' => $confirmed ? 'confirmed' : 'pending',
                'starts_at' => $first->starts_at, 'ends_at' => $end, 'expires_at' => $expires,
                'confirmed_at' => $confirmed ? $now : null, 'confirmed_by' => $confirmed ? $actor->id : null,
                'idempotency_key_hash' => $hash, 'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach ($slots as $slot) {
                $link = ['booking_id' => $id, 'slot_id' => $slot->id, 'venue_court_id' => $court->id];
                DB::table('booking_slots')->insert($link);
                DB::table('active_slot_reservations')->insert($link);
            }
            $this->event($venueId, $id, $confirmed ? 'booking_recorded_confirmed' : 'booking_requested', $actor);
            return DB::table('konfirmasi_bookings')->find($id);
        }, 3);
    }

    private function locate(int $bookingId): array
    {
        $booking = DB::table('konfirmasi_bookings')->find($bookingId);
        abort_unless($booking && !$booking->is_legacy && $booking->venue_court_id, 404);
        $court = DB::table('venue_courts')->find($booking->venue_court_id);
        abort_unless($court, 404);
        return [$booking, (int) $court->lapangan_id];
    }

    public function confirm(User $actor, int $bookingId): object
    {
        [, $venue] = $this->locate($bookingId);
        $result = DB::transaction(function () use ($actor, $bookingId, $venue) {
            $this->lockVenue($venue, $actor);
            $this->expireLocked($venue);
            $booking = DB::table('konfirmasi_bookings')->where('id', $bookingId)->lockForUpdate()->first();
            if ($booking->status === 'confirmed') {
                return $booking;
            }
            if ($booking->status !== 'pending') {
                return null; // Commit expiry before reporting the rejected action.
            }
            $confirmedAt = $this->now();
            if (!$booking->expires_at || $booking->expires_at <= $confirmedAt->format('Y-m-d H:i:s')) {
                $this->expireLocked($venue);
                return null;
            }
            DB::table('konfirmasi_bookings')->where('id', $bookingId)->update([
                'status' => 'confirmed', 'confirmed_at' => $confirmedAt, 'confirmed_by' => $actor->id, 'updated_at' => $confirmedAt,
            ]);
            $this->event($venue, $bookingId, 'booking_confirmed', $actor);
            return DB::table('konfirmasi_bookings')->find($bookingId);
        }, 3);
        if (!$result) {
            $this->fail('Booking sudah kedaluwarsa atau dibatalkan.');
        }
        return $result;
    }

    public function cancel(User $actor, int $bookingId, string $reason): object
    {
        Validator::make(compact('reason'), ['reason' => 'required|string|max:1000'])->validate();
        [, $venue] = $this->locate($bookingId);
        $result = DB::transaction(function () use ($actor, $bookingId, $venue, $reason) {
            $this->lockVenue($venue, $actor);
            $this->expireLocked($venue);
            $booking = DB::table('konfirmasi_bookings')->where('id', $bookingId)->lockForUpdate()->first();
            if ($booking->status === 'canceled') {
                return $booking;
            }
            if (!in_array($booking->status, ['pending', 'confirmed'], true) || $booking->starts_at <= $this->now()->format('Y-m-d H:i:s')) {
                return null;
            }
            DB::table('konfirmasi_bookings')->where('id', $bookingId)->update([
                'status' => 'canceled', 'canceled_at' => $this->now(), 'canceled_by' => $actor->id,
                'cancellation_reason' => $reason, 'updated_at' => $this->now(),
            ]);
            DB::table('active_slot_reservations')->where('booking_id', $bookingId)->delete();
            $this->event($venue, $bookingId, 'booking_canceled', $actor, $reason);
            return DB::table('konfirmasi_bookings')->find($bookingId);
        }, 3);
        if (!$result) {
            $this->fail('Booking tidak dapat dibatalkan karena sudah mulai atau tidak aktif.');
        }
        return $result;
    }

    public function expireForVenue(int $venueId): int
    {
        return DB::transaction(function () use ($venueId) {
            $this->lockVenue($venueId);
            return $this->expireLocked($venueId);
        }, 3);
    }

    /** Requires venue mutex; release all slots together, retaining booking_slots forever. */
    private function expireLocked(int $venueId): int
    {
        $ids = DB::table('konfirmasi_bookings as b')->join('venue_courts as c', 'c.id', '=', 'b.venue_court_id')
            ->where('c.lapangan_id', $venueId)->where('b.is_legacy', false)->where('b.status', 'pending')
            ->whereNotNull('b.expires_at')->where('b.expires_at', '<=', $this->now()->format('Y-m-d H:i:s'))
            ->orderBy('b.id')->select('b.id')->lockForUpdate()->pluck('b.id');
        foreach ($ids as $id) {
            DB::table('konfirmasi_bookings')->where('id', $id)->update(['status' => 'expired', 'updated_at' => $this->now()]);
            DB::table('active_slot_reservations')->where('booking_id', $id)->delete();
            $this->event($venueId, $id, 'booking_expired', null);
        }
        return $ids->count();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Lapangan;
use App\Services\CourtOperations;
use App\Services\HourlyBooking;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

final class PublicHourlyBookingController
{
    public function page(Request $request, Lapangan $lapangan, CourtOperations $operations)
    {
        $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        $minimum = $today->toDateString();
        $maximum = $today->addDays($lapangan->booking_window_days - 1)->toDateString();
        $validator = Validator::make($request->query(), [
            'tanggal' => "nullable|date_format:Y-m-d|after_or_equal:$minimum|before_or_equal:$maximum",
        ]);
        if (!$request->expectsJson() && $validator->fails()) {
            return redirect()->route('cms.cek-booking', $lapangan)->withErrors($validator);
        }
        $date = $validator->validate()['tanggal'] ?? $minimum;

        return response()->view('cms.hourly-booking', [
            'lapangan' => $lapangan, 'date' => $date, 'minimum' => $minimum, 'maximum' => $maximum,
            'courts' => DB::table('venue_courts')->where('lapangan_id', $lapangan->id)->where('is_active', true)->orderBy('name')->get(),
            'slots' => collect($operations->availability($lapangan->id, $date))->groupBy('court_id'),
            'key' => (string) Str::uuid(), 'serverNow' => CarbonImmutable::now('Asia/Jakarta')->toIso8601String(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function availability(Request $request, Lapangan $lapangan, CourtOperations $operations)
    {
        abort_unless($lapangan->hourly_booking_enabled, 404);
        $date = $request->validate(['tanggal' => 'required|date_format:Y-m-d'])['tanggal'];

        $slots = array_map(fn ($slot) => [
            'id' => $slot['id'], 'available' => $slot['available'], 'label' => $slot['label'],
        ], $operations->availability($lapangan->id, $date));

        return response()->json([
            'slots' => $slots, 'now' => CarbonImmutable::now('Asia/Jakarta')->toIso8601String(),
        ])->header('Cache-Control', 'no-store');
    }

    public function store(Request $request, Lapangan $lapangan, HourlyBooking $service)
    {
        abort_unless($lapangan->hourly_booking_enabled, 404);
        $data = $request->validate([
            'slot_ids' => 'required|array|min:1|max:24', 'slot_ids.*' => 'integer|min:1|distinct',
            'nama' => 'required|string|max:255', 'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9][0-9 ()-]{6,28}$/'],
            'key' => 'required|uuid',
        ]);

        $service->reserve($lapangan->id, $data['slot_ids'], $data['nama'], $data['phone'], $data['key'], $this->scope($request));

        return redirect()->route('booking.hourly.receipt', ['lapangan' => $lapangan, 'key' => $data['key']], 303);
    }

    public function receipt(Request $request, Lapangan $lapangan, string $key, HourlyBooking $service)
    {
        abort_unless(Schema::hasTable('venue_courts') && Schema::hasColumn('konfirmasi_bookings', 'idempotency_key_hash'), 404);
        $query = DB::table('konfirmasi_bookings as b')
            ->join('venue_courts as c', 'c.id', '=', 'b.venue_court_id')
            ->where('c.lapangan_id', $lapangan->id)->where('b.is_legacy', false)->where('b.source', 'website')
            ->where('b.idempotency_key_hash', hash('sha256', $lapangan->id.'|'.$this->scope($request).'|'.$key))
            ->select('b.id', 'b.status', 'b.starts_at', 'b.ends_at', 'b.expires_at', 'b.durasi', 'c.name as court_name');
        $booking = (clone $query)->first();
        abort_unless($booking, 404);

        if ($booking->status === 'pending') {
            $service->expireForVenue($lapangan->id);
            $booking = $query->first();
        }

        return response()->view('cms.hourly-receipt', compact('lapangan', 'booking'))
            ->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }

    private function scope(Request $request): string
    {
        return hash('sha256', 'public-hourly|'.$request->session()->getId());
    }
}

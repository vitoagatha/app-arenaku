<?php

namespace App\Http\Controllers;

use App\Models\Lapangan;
use App\Services\CourtOperations;
use App\Services\HourlyBooking;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller;

final class DashboardOperationsController extends Controller
{
    public static function ready(): bool
    {
        return Schema::hasTable('venue_courts') && Schema::hasTable('active_slot_reservations')
            && Schema::hasColumn('konfirmasi_bookings', 'is_legacy');
    }

    private function venue(Request $request, int $id): object
    {
        return Lapangan::query()->when($request->user()->role !== 'admin', fn ($q) => $q->where('user_id', $request->user()->id))->findOrFail($id);
    }

    public function operations(Request $request, int $venue)
    {
        $lapangan = $this->venue($request, $venue);
        if (!self::ready()) {
            return view('dashboard.hourly.unavailable', compact('lapangan'));
        }
        $data = $request->validate(['date' => 'nullable|date_format:Y-m-d']);
        $date = $data['date'] ?? CarbonImmutable::now('Asia/Jakarta')->format('Y-m-d');
        $courts = DB::table('venue_courts')->where('lapangan_id', $venue)->orderBy('name')->get();
        $slots = app(CourtOperations::class)->availability($venue, $date);
        if ($request->wantsJson()) { return response()->json(['slots'=>$slots,'now'=>CarbonImmutable::now('Asia/Jakarta')->toIso8601String()])->header('Cache-Control','no-store'); }
        $closures = DB::table('court_closures')->whereIn('venue_court_id', $courts->pluck('id'))->whereNull('reopened_at')->whereDate('starts_at', $date)->get();
        $exception = DB::table('operating_exceptions')->where('lapangan_id', $venue)->where('date', $date)->first();
        return view('dashboard.hourly.operations', compact('lapangan', 'courts', 'slots', 'closures', 'date', 'exception') + ['key' => (string) Str::uuid()]);
    }

    public function save(Request $request, int $venue)
    {
        $this->venue($request, $venue);
        abort_unless(self::ready(), 409, 'Pengaturan per jam belum tersedia.');
        $data = $request->validate([
            'action' => 'required|in:configure,court,exception,close,reopen,generate,reserve',
            'date' => 'nullable|date_format:Y-m-d',
        ]);
        $actor = $request->user();
        $ops = app(CourtOperations::class);
        switch ($data['action']) {
            case 'configure':
                abort_unless($actor->role === 'admin', 403);
                $p = $request->validate(['open' => 'required|string', 'close' => 'required|string', 'days' => 'required|integer|min:1|max:365', 'reason' => 'required|string|max:1000', 'contact' => ['nullable','regex:/^\+?[0-9 ()-]{7,25}$/']]);
                DB::transaction(function () use ($ops, $actor, $venue, $p) {
                    $ops->configure($actor, $venue, $p['open'], $p['close'], $p['days'], $p['reason']);
                    DB::table('lapangan')->where('id', $venue)->update(['booking_contact' => $p['contact'] ?? null]);
                    DB::table('activity_logs')->insert(['lapangan_id' => $venue, 'actor_id' => $actor->id, 'subject_type' => 'venue', 'subject_id' => $venue, 'action' => 'contact_updated', 'reason' => $p['reason'], 'occurred_at' => CarbonImmutable::now('Asia/Jakarta')]);
                });
                break;
            case 'court':
                abort_unless($actor->role === 'admin', 403);
                $p = $request->validate(['name' => 'required|string|max:100', 'verified' => 'accepted', 'existing_id' => 'nullable|integer']);
                DB::transaction(function () use ($venue, $p, $actor) {
                    DB::table('lapangan')->where('id', $venue)->lockForUpdate()->first();
                    if (!empty($p['existing_id'])) {
                        abort_unless(DB::table('venue_courts')->where('id', $p['existing_id'])->where('lapangan_id', $venue)->exists(), 404);
                        DB::table('venue_courts')->where('id', $p['existing_id'])->update(['is_active' => true, 'updated_at' => now()]);
                        DB::table('activity_logs')->insert(['lapangan_id'=>$venue,'actor_id'=>$actor->id,'subject_type'=>'court','subject_id'=>$p['existing_id'],'action'=>'court_verified','occurred_at'=>now()]);
                        return;
                    }
                    if (DB::table('venue_courts')->where('lapangan_id', $venue)->where('name', $p['name'])->exists()) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['name' => 'Court dengan nama ini sudah tercatat.']);
                    }
                    $id = DB::table('venue_courts')->insertGetId(['lapangan_id' => $venue, 'name' => $p['name'], 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
                    DB::table('activity_logs')->insert(['lapangan_id' => $venue, 'actor_id' => $actor->id, 'subject_type' => 'court', 'subject_id' => $id, 'action' => 'court_verified', 'occurred_at' => now()]);
                });
                break;
            case 'exception':
                $p = $request->validate(['date' => 'required|date_format:Y-m-d', 'reason' => 'required|string|max:1000', 'closed' => 'nullable|boolean', 'open' => 'nullable|string', 'close' => 'nullable|string']);
                $ops->exception($actor, $venue, $p['date'], $request->boolean('closed') ? null : ($p['open'] ?? ''), $request->boolean('closed') ? null : ($p['close'] ?? ''), $p['reason']);
                break;
            case 'close':
                $p = $request->validate(['court_id' => 'required|integer', 'date' => 'required|date_format:Y-m-d', 'open' => 'required|string', 'close' => 'required|string', 'reason' => 'required|string|max:1000']);
                abort_unless(DB::table('venue_courts')->where('id', $p['court_id'])->where('lapangan_id', $venue)->exists(), 404);
                $ops->closeCourt($actor, $p['court_id'], $p['date'], $p['open'], $p['close'], $p['reason']);
                break;
            case 'reopen':
                $p = $request->validate(['closure_id' => 'required|integer', 'reason' => 'required|string|max:1000']);
                abort_unless(DB::table('court_closures as x')->join('venue_courts as c', 'c.id', '=', 'x.venue_court_id')->where('x.id', $p['closure_id'])->where('c.lapangan_id', $venue)->exists(), 404);
                $ops->reopen($actor, $p['closure_id'], $p['reason']);
                break;
            case 'generate':
                $count = $ops->generate($venue);
                return back()->with('status', $count.' slot baru ditambahkan. Jadwal yang sudah ada dipertahankan.');
            case 'reserve':
                $p = $request->validate(['slot_ids' => 'required|array', 'slot_ids.*' => 'integer', 'nama' => 'required|string|max:255', 'phone' => 'required|string|max:255', 'source' => 'required|in:walk_in,whatsapp', 'key' => 'required|string', 'confirmed' => 'nullable|boolean']);
                $booking = app(HourlyBooking::class)->reserve($venue, $p['slot_ids'], $p['nama'], $p['phone'], $p['key'], hash('sha256', $request->session()->getId().'|'.$actor->id), $p['source'], $actor, $request->boolean('confirmed'));
                return redirect()->route('booking.detail', $booking->id)->with('status', 'Booking dicatat. Status ini bukan bukti pembayaran.');
        }
        if (in_array($data['action'], ['configure','court','exception'], true)) { $ops->generate($venue); }
        return redirect()->route('jadwal.operations', ['venue' => $venue, 'date' => $data['date'] ?? null])->with('status', 'Perubahan tersimpan.');
    }

    private function bookingQuery(Request $request)
    {
        return DB::table('konfirmasi_bookings as b')
            ->leftJoin('jadwal_lapangan as j', 'j.id', '=', 'b.jadwal_lapangan_id')
            ->leftJoin('venue_courts as c', 'c.id', '=', 'b.venue_court_id')
            ->leftJoin('courts as legacy_court', 'legacy_court.id', '=', 'j.court_id')
            ->join('lapangan as l', 'l.id', '=', DB::raw('COALESCE(c.lapangan_id,j.lapangan_id)'))
            ->when($request->user()->role !== 'admin', fn ($q) => $q->where('l.user_id', $request->user()->id));
    }

    public function bookings(Request $request)
    {
        $request->validate(['status' => 'nullable|in:pending,confirmed,canceled,expired', 'date' => 'nullable|date_format:Y-m-d', 'search'=>'nullable|string|max:100', 'sort'=>'nullable|in:newest,name,phone,venue,court,start,status,duration,source', 'direction'=>'nullable|in:asc,desc']);
        $venueIds = Lapangan::query()->when($request->user()->role !== 'admin', fn ($q) => $q->where('user_id', $request->user()->id))->pluck('id');
        foreach ($venueIds as $id) { app(HourlyBooking::class)->expireForVenue($id); }
        $query = $this->bookingQuery($request);
        $pending = (clone $query)->where('b.is_legacy', false)->where('b.status', 'pending')->count();
        if ($request->wantsJson()) {
            return response()->json(['pending'=>$pending,'revision'=>(clone $query)->max('b.updated_at')])->header('Cache-Control','no-store');
        }
        $sorts = ['newest'=>'b.id','name'=>'b.nama','phone'=>'b.no_whatsapp','venue'=>'l.nama_lapangan','court'=>DB::raw('COALESCE(c.name,legacy_court.nama_courts)'),'start'=>DB::raw('COALESCE(b.starts_at,TIMESTAMP(j.tanggal,j.jam_awal))'),'status'=>'b.status','duration'=>'b.durasi','source'=>'b.source'];
        $rows = $query->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->input('search').'%';
                $q->where(fn ($match) => $match->where('b.nama','like',$term)->orWhere('b.no_whatsapp','like',$term)->orWhere('l.nama_lapangan','like',$term)->orWhere('c.name','like',$term)->orWhere('legacy_court.nama_courts','like',$term));
            })->when($request->filled('status'), fn ($q) => $q->where('b.status', $request->status))
            ->when($request->status === 'pending', fn ($q) => $q->where('b.is_legacy', false))
            ->when($request->filled('date'), fn ($q) => $q->whereRaw('DATE(COALESCE(b.starts_at,TIMESTAMP(j.tanggal,j.jam_awal))) = ?', [$request->date]))
            ->select('b.*', 'l.nama_lapangan', DB::raw('COALESCE(c.name,legacy_court.nama_courts) as court_name'), 'j.tanggal as legacy_date', 'j.jam_awal', 'j.jam_akhir')
            ->orderBy($sorts[$request->input('sort') ?: 'newest'], $request->input('direction') ?: 'desc')->orderBy('b.id')->paginate(20)->withQueryString();
        return view('dashboard.hourly.bookings', compact('rows', 'pending'));
    }

    public function detail(Request $request, int $booking)
    {
        abort_unless(self::ready(), 409);
        $query = $this->bookingQuery($request)->where('b.id', $booking)->select('b.*', 'l.nama_lapangan', 'l.id as venue_id', 'j.tanggal as legacy_date', 'j.jam_awal', 'j.jam_akhir')->selectRaw('COALESCE(c.name,legacy_court.nama_courts) as court_name');
        $row = (clone $query)->first();
        abort_unless($row, 404);
        if (!$row->is_legacy) { app(HourlyBooking::class)->expireForVenue($row->venue_id); $row = (clone $query)->first(); }
        $slots = DB::table('booking_slots as x')->join('schedule_slots as s', 's.id', '=', 'x.slot_id')->where('x.booking_id', $booking)->orderBy('s.starts_at')->select('s.*')->get();
        $events = DB::table('activity_logs')->where('subject_type', 'booking')->where('subject_id', $booking)->orderBy('id')->get();
        return view('dashboard.hourly.detail', compact('row', 'slots', 'events'));
    }

    public function transition(Request $request, int $booking, HourlyBooking $service)
    {
        abort_unless(self::ready(), 409);
        abort_unless($this->bookingQuery($request)->where('b.id', $booking)->exists(), 404);
        $p = $request->validate(['action' => 'required|in:confirm,cancel', 'reason' => 'required_if:action,cancel|nullable|string|max:1000']);
        if ($p['action'] === 'confirm') { $service->confirm($request->user(), $booking); }
        else { $service->cancel($request->user(), $booking, $p['reason']); }
        return redirect()->route('booking.detail', $booking)->with('status', 'Status booking diperbarui.');
    }
}

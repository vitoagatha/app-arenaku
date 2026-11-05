<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JadwalLapangan;
use App\Models\Lapangan;
use App\Models\KonfirmasiBooking;
use Illuminate\Routing\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        if (DashboardOperationsController::ready()) {
            return app(DashboardOperationsController::class)->bookings($request);
        }
        $bookings = $this->accessibleBookings()->with('jadwalLapangan.lapangan', 'jadwalLapangan.court')->get();
        return view('dashboard.booking.index', compact('bookings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'jadwal_lapangan_id' => 'required|exists:jadwal_lapangan,id',
            'nama' => 'required|string',
            'no_whatsapp' => 'required|string',
            'durasi' => 'required|integer',
        ]);

        [$konfirmasiBooking, $jadwalLapangan] = DB::transaction(function () use ($request) {
            $jadwal = JadwalLapangan::findOrFail($request->jadwal_lapangan_id);
            $venue = Lapangan::whereKey($jadwal->lapangan_id)->lockForUpdate()->firstOrFail();
            abort_if($venue->hourly_booking_enabled, 409, 'Venue ini memakai jadwal per jam. Buka kembali halaman jadwal.');
            $jadwal = JadwalLapangan::whereKey($jadwal->id)->lockForUpdate()->firstOrFail();
            abort_unless($jadwal->lapangan_id === $venue->id, 409, 'Venue jadwal berubah. Buka kembali halaman jadwal.');
            $booking = KonfirmasiBooking::create($request->only('jadwal_lapangan_id', 'nama', 'no_whatsapp', 'durasi'));
            $jadwal->status = 'booking';
            $jadwal->save();
            return [$booking, $jadwal];
        });

        // Mengambil data lapangan terkait
        $lapangan = $jadwalLapangan->lapangan;
        $jadwal = $jadwalLapangan; // Menambahkan variabel $jadwal untuk dikirim ke view

        return view('cms.booking-success', compact('lapangan', 'jadwal', 'konfirmasiBooking'));
    }

    public function edit($id)
    {
        $booking = $this->accessibleBookings()->findOrFail($id);
        if (!$booking) {
            return redirect()->route('booking.index')->with('error', 'Booking not found.');
        }

        return view('dashboard.booking.edit', compact('booking'));
    }

    public function update(Request $request, $id)
    {
        $booking = $this->accessibleBookings()->findOrFail($id);

        $request->validate([
            'nama' => 'required|string',
            'no_whatsapp' => 'required|string',
            'durasi' => 'required|integer',
        ]);

        if (!$booking) {
            return redirect()->route('booking.index')->with('error', 'Booking not found.');
        }

        $booking->update([
            'nama' => $request->nama,
            'no_whatsapp' => $request->no_whatsapp,
            'durasi' => $request->durasi,
        ]);

        return redirect()->route('booking.index')->with('success', 'Booking berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $booking = $this->accessibleBookings()->findOrFail($id);
        if (!$booking) {
            return redirect()->route('booking.index')->with('error', 'Booking not found.');
        }

        $booking->delete();
        return redirect()->route('booking.index')->with('success', 'Booking berhasil dihapus!');
    }

    private function accessibleBookings(): Builder
    {
        $user = Auth::user();
        abort_unless($user && in_array($user->role, ['admin', 'penjaga'], true), 403);

        return KonfirmasiBooking::query()->whereNotNull('jadwal_lapangan_id')->when($user->role !== 'admin', function (Builder $query) use ($user) {
            $query->whereHas('jadwalLapangan.lapangan', fn (Builder $venue) => $venue->where('user_id', $user->id));
        });
    }
}

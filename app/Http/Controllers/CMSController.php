<?php

namespace App\Http\Controllers;

use App\Models\Lapangan;
use Illuminate\Http\Request;
use App\Models\JadwalLapangan;
use Illuminate\Routing\Controller;

class CMSController extends Controller
{
    public function index()
    {
        $lapangans = Lapangan::paginate(3);
        return view('cms.index', compact('lapangans'));
    }

    public function search(Request $request)
    {
        $search = $request->input('search');

        $lapangans = Lapangan::where('nama_lapangan', 'like', '%' . $search . '%')->paginate(3); // Ganti 5 dengan jumlah item per halaman yang diinginkan

        return view('cms.index', compact('lapangans'));
    }

    public function cekBooking(String $id)
    {
        $lapangan = Lapangan::findOrFail($id);
        if (!$lapangan) {
            return redirect()->route('/')->with('error', 'Lapangan not found.');
        }

        return view('cms.cek-booking', compact('lapangan'));
    }

    public function checkAvailability(Request $request, String $id)
    {
        $lapangan = Lapangan::findOrFail($id);
        if (!$lapangan) {
            return redirect()->route('/')->with('error', 'Lapangan not found.');
        }

        $request->validate([
            'tanggal' => 'required|date',
        ]);

        $tanggal = $request->input('tanggal');
        $jadwalLapangan = JadwalLapangan::with('court')->where('lapangan_id', $lapangan->id)
            ->whereDate('tanggal', $tanggal)
            ->get();

        return view('cms.cek-booking', compact('lapangan', 'jadwalLapangan', 'tanggal'));
    }

    public function confirmBooking($id)
    {
        $jadwal = JadwalLapangan::findOrFail($id);
        if (!$jadwal) {
            return redirect()->route('/')->with('error', 'Jadwal not found.');
        }

        $lapangan = $jadwal->lapangan; // Mengakses relasi Lapangan dari JadwalLapangan

        return view('cms.konfirmasi-booking', compact('lapangan', 'jadwal'));
    }
}

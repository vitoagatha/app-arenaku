<?php

namespace App\Http\Controllers;

use App\Models\JadwalLapangan;
use App\Models\Lapangan;
use App\Models\Court;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class JadwalController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            $lapangans = Lapangan::all();
        } else {
            $lapangans = Lapangan::where('user_id', $user->id)->get();
        }

        return view('dashboard.jadwal.index', compact('lapangans'));
    }

    public function jadwal(String $id)
    {
        try {
            $lapangan = $this->findLapanganById($id);

            if ($lapangan->user_id !== Auth::id() && Auth::user()->role !== 'admin') {
                return redirect()->route('jadwal.index')->with('error', 'You do not have permission to view this jadwal.');
            }

            $jadwalLapangan = JadwalLapangan::with('court')->where('lapangan_id', $lapangan->id)->get();
            $courts = Court::all();

            return view('dashboard.jadwal.jadwal', compact('lapangan', 'jadwalLapangan', 'courts'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('jadwal.index')->with('error', 'Lapangan not found.');
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'court_id' => 'required|exists:courts,id',
            'lapangan_id' => 'required|exists:lapangan,id',
            'jam_awal' => 'required',
            'jam_akhir' => 'required',
            'tanggal' => 'required|date',
        ]);

        $lapangan = $this->findLapanganById($request->lapangan_id);

        JadwalLapangan::create($request->all());

        return redirect()->back()->with('success', 'Jadwal berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'court_id' => 'required|exists:courts,id',
            'lapangan_id' => 'required|exists:lapangan,id',
            'jam_awal' => 'required',
            'jam_akhir' => 'required',
            'tanggal' => 'required|date',
            'status' => 'required',
        ]);

        $jadwal = JadwalLapangan::findOrFail($id);
        $lapangan = $this->findLapanganById($jadwal->lapangan_id);

        if ($lapangan->user_id !== Auth::id() && Auth::user()->role !== 'admin') {
            return redirect()->route('jadwal.index')->with('error', 'You do not have permission to delete this jadwal.');
        }

        $jadwal->update($request->all());

        return redirect()->back()->with('success', 'Jadwal berhasil diupdate');
    }

    public function destroy($id)
    {
        $jadwal = JadwalLapangan::findOrFail($id);
        $lapangan = $this->findLapanganById($jadwal->lapangan_id);

        if ($lapangan->user_id !== Auth::id() && Auth::user()->role !== 'admin') {
            return redirect()->route('jadwal.index')->with('error', 'You do not have permission to delete this jadwal.');
        }

        $jadwal->delete();

        return redirect()->back()->with('success', 'Jadwal berhasil dihapus');
    }

    /**
     * Find Lapangan by ID, considering user permissions.
     */
    private function findLapanganById($id)
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            $lapangan = Lapangan::findOrFail($id);
        } else {
            $lapangan = Lapangan::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        }

        return $lapangan;
    }
}

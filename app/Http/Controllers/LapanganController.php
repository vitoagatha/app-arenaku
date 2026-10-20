<?php

namespace App\Http\Controllers;

use App\Models\Lapangan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class LapanganController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            $lapangans = Lapangan::all();
        } else {
            $lapangans = Lapangan::where('user_id', $user->id)->get();
        }

        return view('dashboard.lapangan.index', compact('lapangans'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.lapangan.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_lapangan' => 'required|string|max:155',
            'alamat' => 'required|string',
            'deskripsi' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $lapangan = new Lapangan();
        $lapangan->nama_lapangan = $request->input('nama_lapangan');
        $lapangan->alamat = $request->input('alamat');
        $lapangan->deskripsi = $request->input('deskripsi');
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('uploads', 'public');
            $lapangan->image = $imagePath;
        }
        $lapangan->user_id = Auth::user()->id;
        $lapangan->save();

        return redirect()->route('lapangan.index')->with('success', 'Lapangan created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $lapangan = Lapangan::findLapanganById($id);
            return view('dashboard.lapangan.show', compact('lapangan'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('lapangan.index')->with('error', 'Lapangan not found.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $lapangan = Lapangan::findLapanganById($id);
            return view('dashboard.lapangan.edit', compact('lapangan'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('lapangan.index')->with('error', 'Lapangan not found.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_lapangan' => 'required|string|max:155',
            'alamat' => 'required|string',
            'deskripsi' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $lapangan = Lapangan::findLapanganById($id);

            // Store the old image path before updating
            $oldImagePath = $lapangan->image;

            $lapangan->nama_lapangan = $request->input('nama_lapangan');
            $lapangan->alamat = $request->input('alamat');
            $lapangan->deskripsi = $request->input('deskripsi');
            if ($request->hasFile('image')) {
                // Delete the old image if it exists
                if ($oldImagePath) {
                    $this->deleteImage($oldImagePath);
                }

                $imagePath = $request->file('image')->store('uploads', 'public');
                $lapangan->image = $imagePath;
            }
            $user->save();

            return redirect()->route('lapangan.index')->with('success', 'Lapangan updated successfully.');
        } catch (ModelNotFoundException $e) {
            return redirect()->route('lapangan.index')->with('error', 'Lapangan not found.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $lapangan = Lapangan::findLapanganById($id);

            $oldImagePath = $lapangan->image;

            if ($oldImagePath) {
                $this->deleteImage($oldImagePath);
            }

            $lapangan->delete();
            return redirect()->route('lapangan.index')->with('success', 'Lapangan deleted successfully.');
        } catch (ModelNotFoundException $e) {
            return redirect()->route('lapangan.index')->with('error', 'Lapangan not found.');
        }
    }

    /**
     * Delete image from storage.
     */
    private function deleteImage($path)
    {
        // Get the full path to the image file
        $filePath = storage_path('app/public/' . $path);

        // Check if the file exists before attempting deletion
        if (File::exists($filePath)) {
            // Delete the file
            File::delete($filePath);
        }
    }

    /**
     * Find Lapangan by ID, considering user permissions.
     */
    private function findLapanganById($id)
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return Lapangan::findLapanganById($id);
        } else {
            return Lapangan::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        }
    }
}

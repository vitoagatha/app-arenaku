<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Lapangan;
use App\Services\HourlyMaintenance;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = auth()->user();

        $jumlahLapangan = $user->role === 'admin'
            ? Lapangan::count()
            : Lapangan::where('user_id', $user->id)->count();

        $widget = [
            'users' => User::count(),
            'lapangan' => $jumlahLapangan,
        ];

        $health = null;
        if ($user->role === 'admin') {
            $maintenance = app(HourlyMaintenance::class);
            $health = $maintenance->ready() ? $maintenance->health() : null;
        }

        return view('home', compact('widget', 'health'));
    }
}

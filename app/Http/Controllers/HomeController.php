<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Lapangan;
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

        return view('home', compact('widget'));
    }
}

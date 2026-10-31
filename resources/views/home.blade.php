@extends('layouts.admin')
@section('main-content')
    @php($admin = Auth::user()->role === 'admin')
    <div class="akd-page-heading"><h1>{{ $admin ? 'Ringkasan pengelolaan' : 'Siapkan lapangan untuk bermain' }}</h1><p>{{ $admin ? 'Kelola tempat, jadwal, dan data Arenaku dari satu ruang kerja.' : 'Mulai dari jadwal lapangan yang menjadi tanggung jawabmu.' }}</p></div>
    @include('components.dashboard.feedback')
    <dl class="akd-stats">
        <div><dt>{{ $admin ? 'Lapangan terdaftar' : 'Lapangan yang ditugaskan' }}</dt><dd>{{ $widget['lapangan'] }}</dd></div>
        @if($admin)<div><dt>Pengguna terdaftar</dt><dd>{{ $widget['users'] }}</dd></div>@endif
    </dl>
    <section class="akd-surface" aria-labelledby="tasks-title"><h2 id="tasks-title">{{ $admin ? 'Akses pengelolaan' : 'Tugas operasional' }}</h2><p class="akd-muted">{{ $admin ? 'Pilih data yang ingin kamu kelola.' : 'Buka jadwal atau periksa informasi booking melalui menu berikut.' }}</p>
        @if(!$admin && $widget['lapangan'] === 0)<p class="akd-note">Belum ada lapangan yang ditugaskan ke akunmu. Hubungi admin untuk penugasan lapangan.</p>@endif
        <div class="akd-tasks">
            @include('components.dashboard.task-link', ['route' => 'jadwal.index', 'label' => 'Atur jadwal', 'description' => 'Pilih lapangan untuk mengelola tanggal dan jam main.', 'icon' => 'calendar-alt'])
            @include('components.dashboard.task-link', ['route' => 'booking.index', 'label' => 'Daftar booking', 'description' => 'Lihat data pemesan dan jadwal booking.', 'icon' => 'clipboard-list'])
            @include('components.dashboard.task-link', ['route' => 'lapangan.index', 'label' => $admin ? 'Kelola lapangan' : 'Lapangan saya', 'description' => 'Buka informasi dan foto lapangan.', 'icon' => 'building'])
            @if($admin)
                @include('components.dashboard.task-link', ['route' => 'courts.index', 'label' => 'Data court', 'description' => 'Kelola unit court untuk penjadwalan.', 'icon' => 'list'])
                @include('components.dashboard.task-link', ['route' => 'users.index', 'label' => 'Kelola pengguna', 'description' => 'Buka data akun dan peran pengguna.', 'icon' => 'users'])
            @endif
        </div>
    </section>
@endsection

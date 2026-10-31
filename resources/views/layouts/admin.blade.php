@php
    $isAdmin = Auth::user()->role === 'admin';
    $roleLabel = $isAdmin ? 'Admin' : ucfirst(Auth::user()->role);
    $pageTitle = request()->routeIs('profile*') ? 'Profil saya' : (request()->routeIs('home') ? 'Ringkasan' : (request()->routeIs('lapangan.*') ? 'Lapangan' : (request()->routeIs('courts.*') ? 'Court' : (request()->routeIs('jadwal.*') ? 'Jadwal lapangan' : (request()->routeIs('booking.*') ? 'Booking' : (request()->routeIs('users.*') ? 'Pengguna' : 'Pengelolaan'))))));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }} | Arenaku</title>
    <meta name="description" content="Pengelolaan lapangan, jadwal, dan booking Arenaku.">
    <link href="{{ asset('assets/img/icon_arenaku.jpg') }}" rel="icon" type="image/jpeg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/sb-admin-2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/arenaku-dashboard.css') }}" rel="stylesheet">
</head>
<body class="ak-dashboard" id="page-top">
    <a href="#dashboard-main" class="akd-skip">Langsung ke konten</a>
    <div class="akd-shell">
        <aside class="akd-sidebar" id="dashboard-navigation" aria-label="Menu pengelolaan">
            <div class="akd-sidebar-brand">
                <a class="akd-brand" href="{{ route('home') }}" aria-label="Arenaku, ringkasan"><img src="{{ asset('assets/img/logo_arenaku.jpg') }}" width="3656" height="2680" alt="Arenaku"></a>
                <button class="akd-icon-button akd-close" type="button" data-nav-close aria-label="Tutup navigasi"><i class="fas fa-times" aria-hidden="true"></i></button>
            </div>
            <p class="akd-sidebar-caption">Pengelolaan lapangan</p>
            <nav aria-label="Navigasi dashboard">
                <ul class="akd-nav-list">
                    @include('components.dashboard.nav-link', ['route' => 'home', 'pattern' => 'home', 'label' => 'Ringkasan', 'icon' => 'th-large'])
                </ul>
                <p class="akd-nav-group">Operasional</p>
                <ul class="akd-nav-list">
                    @include('components.dashboard.nav-link', ['route' => 'lapangan.index', 'pattern' => 'lapangan.*', 'label' => 'Lapangan', 'icon' => 'building'])
                    @include('components.dashboard.nav-link', ['route' => 'jadwal.index', 'pattern' => 'jadwal.*', 'label' => 'Atur jadwal', 'icon' => 'calendar-alt'])
                    @include('components.dashboard.nav-link', ['route' => 'booking.index', 'pattern' => 'booking.*', 'label' => 'Daftar booking', 'icon' => 'clipboard-list'])
                </ul>
                @if ($isAdmin)
                    <p class="akd-nav-group">Administrasi</p>
                    <ul class="akd-nav-list">
                        @include('components.dashboard.nav-link', ['route' => 'courts.index', 'pattern' => 'courts.*', 'label' => 'Data court', 'icon' => 'list'])
                        @include('components.dashboard.nav-link', ['route' => 'users.index', 'pattern' => 'users.*', 'label' => 'Pengguna', 'icon' => 'users'])
                    </ul>
                @endif
            </nav>
            <a class="akd-public-link" href="{{ route('/') }}"><i class="fas fa-external-link-alt" aria-hidden="true"></i>Lihat halaman publik</a>
        </aside>
        <button class="akd-backdrop" type="button" data-nav-close aria-label="Tutup navigasi" hidden></button>
        <div class="akd-workspace">
            <header class="akd-header">
                <div class="akd-header-heading"><button class="akd-icon-button akd-menu" type="button" data-nav-toggle aria-controls="dashboard-navigation" aria-expanded="false" aria-label="Buka navigasi"><i class="fas fa-bars" aria-hidden="true"></i></button><span>{{ $pageTitle }}</span></div>
                <div class="akd-account"><a href="{{ route('profile') }}" class="akd-account-link" @if(request()->routeIs('profile')) aria-current="page" @endif><span class="akd-user-name">{{ Auth::user()->fullName }}</span><small>{{ $roleLabel }} · Profil</small></a><button class="akd-icon-button" type="button" data-toggle="modal" data-target="#logoutModal" aria-label="Keluar dari akun"><i class="fas fa-sign-out-alt" aria-hidden="true"></i></button></div>
            </header>
            <main class="akd-main" id="dashboard-main" tabindex="-1">@yield('main-content')</main>
            <footer class="akd-footer">&copy; {{ now()->year }} Arenaku <span>Pengelolaan tempat dan waktu main.</span></footer>
        </div>
    </div>
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="logout-title" aria-hidden="true">
        <div class="modal-dialog" role="document"><div class="modal-content">
            <div class="modal-header"><h2 class="modal-title h5" id="logout-title">Keluar dari Arenaku?</h2><button class="close" type="button" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div>
            <div class="modal-body">Sesi pengelolaan akan diakhiri. Kamu dapat masuk kembali untuk melanjutkan.</div>
            <div class="modal-footer"><button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button><form action="{{ route('logout') }}" method="POST">@csrf<button class="btn btn-primary" type="submit">Keluar</button></form></div>
        </div></div>
    </div>
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('vendor/jquery-easing/jquery.easing.min.js') }}"></script>
    <script src="{{ asset('js/sb-admin-2.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('js/demo/datatables-demo.js') }}"></script>
    <script src="{{ asset('js/arenaku-dashboard.js') }}"></script>
</body>
</html>

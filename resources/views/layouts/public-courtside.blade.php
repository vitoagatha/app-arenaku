<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Arenaku')</title>
    <meta name="description" content="Temukan lapangan olahraga, lihat jadwal sesuai tanggal pilihanmu, dan mulai booking di Arenaku.">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/img/icon_arenaku.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/img/icon_arenaku.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('css/arenaku-public.css') }}" rel="stylesheet">
</head>
<body class="ak-public">
    <a class="ak-skip" href="#main">Langsung ke konten</a>
    <header class="ak-header">
        <div class="ak-container ak-header-inner">
            <a class="ak-brand" href="{{ route('/') }}" aria-label="Arenaku, beranda"><img class="ak-brand-image" src="{{ asset('assets/img/logo_arenaku.jpg') }}" alt="Arenaku" width="3656" height="2680"></a>
            <nav class="ak-nav" aria-label="Navigasi utama"><a href="{{ route('/') }}#hasil-lapangan">Cari lapangan</a><a class="ak-nav-login" href="{{ route('login') }}">Masuk <span class="ak-desktop-label">pengelola</span><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i></a></nav>
        </div>
    </header>
    @yield('content')
    <footer class="ak-footer">
        <div class="ak-container ak-footer-inner">
            <div><a class="ak-brand" href="{{ route('/') }}" aria-label="Arenaku, beranda"><img class="ak-brand-image" src="{{ asset('assets/img/logo_arenaku.jpg') }}" alt="Arenaku" width="3656" height="2680" loading="lazy"></a><p>Temukan tempatnya. Tentukan waktu mainmu.</p></div>
            <nav aria-label="Navigasi footer"><a href="{{ route('/') }}#hasil-lapangan">Jelajahi lapangan</a><a href="{{ route('login') }}">Masuk pengelola</a></nav>
            <small>&copy; {{ now()->year }} Arenaku</small>
        </div>
    </footer>
    <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/arenaku-public.js') }}" defer></script>
</body>
</html>

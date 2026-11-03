<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Masuk pengelola | Arenaku')</title>
    <meta name="description" content="@yield('description', 'Masuk ke Arenaku untuk mengelola lapangan, jadwal, dan informasi booking.')">
    <link rel="icon" type="image/jpeg" href="{{ url('assets/img/icon_arenaku.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ url('css/sb-admin-2.min.css') }}" rel="stylesheet">
    <link href="{{ url('css/arenaku-tokens.css') . '?v=' . filemtime(public_path('css/arenaku-tokens.css')) }}" rel="stylesheet">
    <link href="{{ url('css/arenaku-dashboard.css') . '?v=' . filemtime(public_path('css/arenaku-dashboard.css')) }}" rel="stylesheet">
    <link href="{{ url('css/arenaku-login.css') . '?v=' . filemtime(public_path('css/arenaku-login.css')) }}" rel="stylesheet">
</head>
<body class="ak-dashboard ak-login">
    <a class="akd-skip" href="#login-main">Langsung ke formulir</a>
    <header class="ak-login-header"><a class="akd-brand" href="{{ route('/') }}" aria-label="Arenaku, beranda"><img src="{{ url('assets/img/logo_arenaku-640.webp') }}" width="640" height="469" alt="Arenaku"></a><a class="ak-login-back" href="{{ route('/') }}">Kembali ke beranda</a></header>
    <main id="login-main" class="ak-login-main" tabindex="-1">@yield('main-content')</main>
    <footer class="ak-login-footer">&copy; {{ now()->year }} Arenaku</footer>
    <script src="{{ url('js/arenaku-login.js') . '?v=' . filemtime(public_path('js/arenaku-login.js')) }}" defer></script>
</body>
</html>

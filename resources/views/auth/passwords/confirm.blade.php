@extends('layouts.login')
@section('title', 'Konfirmasi kata sandi | Arenaku')
@section('main-content')
<div class="ak-login-panel">
    @include('components.auth.visual')
    <section class="ak-login-form-section akd-management" aria-labelledby="auth-title">
        <h1 id="auth-title">Konfirmasi kata sandi</h1>
        <p class="akd-muted">Masukkan kata sandi akun sebelum melanjutkan.</p>
        @include('components.auth.feedback')
        <form method="POST" action="{{ route('password.confirm') }}" data-login-form data-loading-label="Memproses&#8230;" data-loading-status="Sedang memproses permintaan.">
            @csrf
            <div class="form-group"><label for="password">Kata sandi</label><div class="ak-login-password"><input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror><button class="ak-login-reveal" type="button" aria-controls="password" aria-pressed="false" aria-label="Tampilkan kata sandi" data-password-toggle hidden>Tampilkan</button></div>@error('password')<p class="akd-field-error" id="password-error">{{ $message }}</p>@enderror</div>
            <button class="akd-button ak-login-submit" type="submit">Konfirmasi kata sandi</button>
            <p class="ak-login-status" role="status" aria-live="polite" data-login-status></p>
        </form>
        @if(Route::has('password.request'))<a class="ak-login-return" href="{{ route('password.request') }}">Lupa kata sandi?</a>@endif
        <p class="ak-login-help">Kembali ke halaman masuk <a class="ak-login-return" href="{{ route('login') }}">Masuk</a></p>
    </section>
</div>
@endsection

@extends('layouts.login')
@section('title', 'Lupa kata sandi | Arenaku')
@section('description', 'Minta tautan reset untuk memulihkan akses akun pengelola Arenaku.')
@section('main-content')
<div class="ak-login-panel">
    @include('components.auth.visual')
    <section class="ak-login-form-section akd-management" aria-labelledby="reset-title">
        <h1 id="reset-title">Lupa kata sandi?</h1>
        <p class="akd-muted">Masukkan alamat email akunmu untuk meminta tautan reset kata sandi.</p>
        @include('components.auth.feedback')
        <form method="POST" action="{{ route('password.email') }}" data-login-form data-loading-label="Mengirim tautan…" data-loading-status="Sedang memproses permintaan tautan reset.">
            @csrf
            <div class="form-group"><label for="email">Alamat email</label><input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" autocomplete="email" spellcheck="false" placeholder="nama@contoh.com" required @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>@error('email')<p class="akd-field-error" id="email-error">{{ $message }}</p>@enderror</div>
            <button type="submit" class="akd-button ak-login-submit">Kirim tautan reset</button>
            <p class="ak-login-status" role="status" aria-live="polite" data-login-status></p>
        </form>
        <p class="ak-login-help">Sudah ingat kata sandinya? <a class="ak-login-return" href="{{ route('login') }}">Kembali ke halaman masuk</a></p>
    </section>
</div>
@endsection

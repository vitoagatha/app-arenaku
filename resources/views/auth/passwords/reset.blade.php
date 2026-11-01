@extends('layouts.login')
@section('title', 'Reset kata sandi | Arenaku')
@section('description', 'Buat kata sandi baru untuk memulihkan akses akun pengelola Arenaku.')
@section('main-content')
<div class="ak-login-panel">
    @include('components.auth.visual')
    <section class="ak-login-form-section akd-management" aria-labelledby="reset-title">
        <h1 id="reset-title">Buat kata sandi baru</h1>
        <p class="akd-muted">Isi alamat email akunmu, lalu masukkan kata sandi baru dua kali.</p>
        @include('components.auth.feedback')
        <form method="POST" action="{{ route('password.update') }}" data-login-form data-loading-label="Menyimpan kata sandi…" data-loading-status="Sedang memproses perubahan kata sandi.">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="form-group"><label for="email">Alamat email</label><input type="email" class="form-control" id="email" name="email" value="{{ old('email', $email ?? '') }}" autocomplete="email" spellcheck="false" placeholder="nama@contoh.com" required @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>@error('email')<p class="akd-field-error" id="email-error">{{ $message }}</p>@enderror</div>
            <div class="form-group"><label for="password">Kata sandi baru</label><div class="ak-login-password"><input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror><button class="ak-login-reveal" type="button" aria-controls="password" aria-pressed="false" data-password-toggle hidden>Tampilkan</button></div>@error('password')<p class="akd-field-error" id="password-error">{{ $message }}</p>@enderror</div>
            <div class="form-group"><label for="password_confirmation">Ulangi kata sandi baru</label><div class="ak-login-password"><input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required @error('password_confirmation') aria-invalid="true" aria-describedby="password_confirmation-error" @enderror><button class="ak-login-reveal" type="button" aria-controls="password_confirmation" aria-pressed="false" data-password-toggle hidden>Tampilkan</button></div>@error('password_confirmation')<p class="akd-field-error" id="password_confirmation-error">{{ $message }}</p>@enderror</div>
            <button type="submit" class="akd-button ak-login-submit">Simpan kata sandi baru</button>
            <p class="ak-login-status" role="status" aria-live="polite" data-login-status></p>
        </form>
        <p class="ak-login-help"><a class="ak-login-return" href="{{ route('login') }}">Kembali ke halaman masuk</a></p>
    </section>
</div>
@endsection

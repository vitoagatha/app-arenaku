@extends('layouts.login')
@section('title', 'Daftar akun | Arenaku')
@section('main-content')
<div class="ak-login-panel">
    @include('components.auth.visual')
    <section class="ak-login-form-section akd-management" aria-labelledby="auth-title">
        <h1 id="auth-title">Daftar akun</h1>
        <p class="akd-muted">Isi informasi akun untuk mendaftar di Arenaku.</p>
        @include('components.auth.feedback')
        <form method="POST" action="{{ route('register') }}" data-login-form data-loading-label="Memproses&#8230;" data-loading-status="Sedang memproses permintaan.">
            @csrf
            <div class="form-group"><label for="name">Nama depan</label><input type="text" class="form-control" id="name" name="name" autocomplete="given-name" value="{{ old('name') }}" required @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>@error('name')<p class="akd-field-error" id="name-error">{{ $message }}</p>@enderror</div>
            <div class="form-group"><label for="last_name">Nama belakang</label><input type="text" class="form-control" id="last_name" name="last_name" autocomplete="family-name" value="{{ old('last_name') }}" required @error('last_name') aria-invalid="true" aria-describedby="last_name-error" @enderror>@error('last_name')<p class="akd-field-error" id="last_name-error">{{ $message }}</p>@enderror</div>
            <div class="form-group"><label for="email">Alamat email</label><input type="email" class="form-control" id="email" name="email" autocomplete="email" value="{{ old('email') }}" required @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>@error('email')<p class="akd-field-error" id="email-error">{{ $message }}</p>@enderror</div>
            <div class="form-group"><label for="password">Kata sandi</label><div class="ak-login-password"><input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror><button class="ak-login-reveal" type="button" aria-controls="password" aria-pressed="false" aria-label="Tampilkan kata sandi" data-password-toggle hidden>Tampilkan</button></div>@error('password')<p class="akd-field-error" id="password-error">{{ $message }}</p>@enderror</div>
            <div class="form-group"><label for="password_confirmation">Konfirmasi kata sandi</label><div class="ak-login-password"><input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required @error('password_confirmation') aria-invalid="true" aria-describedby="password_confirmation-error" @enderror><button class="ak-login-reveal" type="button" aria-controls="password_confirmation" aria-pressed="false" aria-label="Tampilkan konfirmasi kata sandi" data-password-toggle hidden>Tampilkan</button></div>@error('password_confirmation')<p class="akd-field-error" id="password_confirmation-error">{{ $message }}</p>@enderror</div>
            <button class="akd-button ak-login-submit" type="submit">Daftar akun</button>
            <p class="ak-login-status" role="status" aria-live="polite" data-login-status></p>
        </form>
        <p class="ak-login-help">Sudah punya akun? <a class="ak-login-return" href="{{ route('login') }}">Masuk</a></p>
    </section>
</div>
@endsection

@extends('layouts.login')
@section('main-content')
<div class="ak-login-panel">
    @include('components.auth.visual')
    <section class="ak-login-form-section akd-management" aria-labelledby="login-title">
        <h1 id="login-title">Masuk pengelola</h1>
        <p class="akd-muted">Gunakan akun admin atau penjaga untuk membuka dashboard Arenaku.</p>
        @if($errors->any())
            <div class="akd-errors" role="alert" tabindex="-1" data-login-errors><h2>Belum berhasil masuk</h2><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        @if(session('status'))<p class="akd-note" role="status">{{ session('status') }}</p>@endif
        <form action="{{ route('login') }}" method="POST" data-login-form>
            @csrf
            <div class="form-group"><label for="email">Alamat email</label><input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" autocomplete="username" spellcheck="false" placeholder="nama@contoh.com" required @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>@error('email')<p class="akd-field-error" id="email-error">{{ $message }}</p>@enderror</div>
            <div class="form-group"><label for="password">Kata sandi</label><div class="ak-login-password"><input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror><button class="ak-login-reveal" type="button" aria-controls="password" aria-pressed="false" data-password-toggle hidden>Tampilkan</button></div>@error('password')<p class="akd-field-error" id="password-error">{{ $message }}</p>@enderror</div>
            @if(Route::has('password.request'))<a class="ak-login-forgot" href="{{ route('password.request') }}">Lupa kata sandi?</a>@endif
            <button class="akd-button ak-login-submit" type="submit">Masuk ke dashboard</button>
            <p class="ak-login-status" role="status" aria-live="polite" data-login-status></p>
        </form>
        <p class="ak-login-help">Untuk mengakses pengelolaan lapangan, gunakan akun yang sudah terdaftar.</p>
    </section>
</div>
@endsection

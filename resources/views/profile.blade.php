@extends('layouts.admin')
@section('main-content')
    <div class="akd-page-heading"><h1>Profil saya</h1><p>Perbarui informasi akun dan kata sandi untuk akses pengelolaan.</p></div>
    @include('components.dashboard.feedback')
    <div class="akd-profile-grid">
        <aside class="akd-surface akd-profile-summary" aria-label="Informasi akun"><span class="akd-avatar" aria-hidden="true">{{ mb_substr(Auth::user()->name, 0, 1) }}</span><h2>{{ Auth::user()->fullName }}</h2><p>{{ Auth::user()->role === 'admin' ? 'Admin' : ucfirst(Auth::user()->role) }}</p><p class="akd-muted">{{ Auth::user()->email }}</p></aside>
        <form class="akd-surface akd-profile-form" method="POST" action="{{ route('profile.update') }}" data-profile-form>
            @csrf
            @method('PUT')
            <fieldset><legend>Informasi pribadi</legend><p class="akd-muted">Nama depan dan alamat email wajib diisi.</p>
                <div class="akd-form-grid">
                    @include('components.dashboard.profile-field', ['name' => 'name', 'label' => 'Nama depan (wajib)', 'type' => 'text', 'autocomplete' => 'given-name'])
                    @include('components.dashboard.profile-field', ['name' => 'last_name', 'label' => 'Nama belakang', 'type' => 'text', 'autocomplete' => 'family-name'])
                    @include('components.dashboard.profile-field', ['name' => 'email', 'label' => 'Alamat email (wajib)', 'type' => 'email', 'autocomplete' => 'email'])
                    @include('components.dashboard.profile-field', ['name' => 'no_handphone', 'label' => 'Nomor handphone', 'type' => 'tel', 'autocomplete' => 'tel'])
                </div>
            </fieldset>
            <fieldset><legend>Ubah kata sandi</legend><p class="akd-muted">Kosongkan bagian ini jika tidak ingin mengubah kata sandi. Kata sandi baru harus berisi 8–12 karakter.</p>
                @include('components.dashboard.profile-field', ['name' => 'current_password', 'label' => 'Kata sandi saat ini', 'type' => 'password', 'autocomplete' => 'current-password'])
                <div class="akd-form-grid">
                    @include('components.dashboard.profile-field', ['name' => 'new_password', 'label' => 'Kata sandi baru', 'type' => 'password', 'autocomplete' => 'new-password'])
                    @include('components.dashboard.profile-field', ['name' => 'password_confirmation', 'label' => 'Ulangi kata sandi baru', 'type' => 'password', 'autocomplete' => 'new-password'])
                </div>
            </fieldset>
            <div class="akd-form-actions"><button class="akd-button" type="submit">Simpan perubahan</button><a href="{{ route('home') }}">Kembali ke ringkasan</a><span role="status" data-profile-status></span></div>
        </form>
    </div>
@endsection

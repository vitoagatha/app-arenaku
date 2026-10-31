@extends('layouts.admin')
@section('main-content')
<div class="akd-management">
<div class="akd-page-heading"><a class="akd-back" href="{{ route('users.index') }}">Kembali ke daftar</a><h1>Tambah pengguna</h1><p>Lengkapi informasi berikut. Isian wajib ditandai pada label.</p></div>
@include('components.dashboard.feedback')
<form class="akd-surface akd-management-form" action="{{ route('users.store') }}" method="POST" data-management-submit>
    @csrf
    @include('components.dashboard.field', ['name' => 'name', 'label' => 'Nama depan', 'value' => '', 'type' => 'text', 'required' => true])
    @include('components.dashboard.field', ['name' => 'last_name', 'label' => 'Nama belakang', 'value' => '', 'type' => 'text', 'required' => true])
    @include('components.dashboard.field', ['name' => 'email', 'label' => 'Alamat email', 'value' => '', 'type' => 'email', 'required' => true])
    @include('components.dashboard.field', ['name' => 'password', 'label' => 'Kata sandi', 'value' => '', 'type' => 'password', 'required' => true, 'autocomplete' => 'new-password'])
<p class="akd-muted">Kata sandi minimal 8 karakter.</p>
    @include('components.dashboard.field', ['name' => 'no_handphone', 'label' => 'Nomor handphone', 'value' => '', 'type' => 'tel', 'required' => false])
    @include('components.dashboard.field', ['name' => 'role', 'label' => 'Peran', 'value' => 'admin', 'type' => 'select', 'required' => true, 'options' => ['admin' => 'Admin', 'penjaga' => 'Penjaga']])
    <div class="akd-form-actions"><button class="akd-button" type="submit">Simpan data</button><a href="{{ route('users.index') }}">Batal</a><span role="status" data-management-status></span></div>
</form>
</div>
@endsection

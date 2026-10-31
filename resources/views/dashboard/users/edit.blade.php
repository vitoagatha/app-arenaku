@extends('layouts.admin')
@section('main-content')
<div class="akd-management">
<div class="akd-page-heading"><a class="akd-back" href="{{ route('users.index') }}">Kembali ke daftar</a><h1>Edit pengguna</h1><p>Lengkapi informasi berikut. Isian wajib ditandai pada label.</p></div>
@include('components.dashboard.feedback')
<form class="akd-surface akd-management-form" action="{{ route('users.update', $user->id) }}" method="POST" data-management-submit>
    @csrf
    @method('PUT')
    @include('components.dashboard.field', ['name' => 'name', 'label' => 'Nama depan', 'value' => $user->name, 'type' => 'text', 'required' => true])
    @include('components.dashboard.field', ['name' => 'last_name', 'label' => 'Nama belakang', 'value' => $user->last_name, 'type' => 'text', 'required' => true])
    @include('components.dashboard.field', ['name' => 'email', 'label' => 'Alamat email', 'value' => $user->email, 'type' => 'email', 'required' => true])
    @include('components.dashboard.field', ['name' => 'password', 'label' => 'Kata sandi', 'value' => '', 'type' => 'password', 'required' => false, 'autocomplete' => 'new-password'])
<p class="akd-muted">Kosongkan kata sandi untuk mempertahankan yang lama. Kata sandi minimal 8 karakter.</p>
    @include('components.dashboard.field', ['name' => 'no_handphone', 'label' => 'Nomor handphone', 'value' => $user->no_handphone, 'type' => 'tel', 'required' => false])
    @include('components.dashboard.field', ['name' => 'role', 'label' => 'Peran', 'value' => $user->role, 'type' => 'select', 'required' => true, 'options' => ['admin' => 'Admin', 'penjaga' => 'Penjaga']])
    <div class="akd-form-actions"><button class="akd-button" type="submit">Simpan perubahan</button><a href="{{ route('users.index') }}">Batal</a><span role="status" data-management-status></span></div>
</form>
</div>
@endsection

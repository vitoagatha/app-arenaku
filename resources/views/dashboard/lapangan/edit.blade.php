@extends('layouts.admin')
@section('main-content')
<div class="akd-management">
<div class="akd-page-heading"><a class="akd-back" href="{{ route('lapangan.index') }}">Kembali ke daftar</a><h1>Edit lapangan</h1><p>Lengkapi informasi berikut. Isian wajib ditandai pada label.</p></div>
@include('components.dashboard.feedback')
<form class="akd-surface akd-management-form" action="{{ route('lapangan.update', $lapangan->id) }}" method="POST" data-management-submit enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('components.dashboard.field', ['name' => 'nama_lapangan', 'label' => 'Nama lapangan', 'value' => $lapangan->nama_lapangan, 'type' => 'text', 'required' => true])
    @include('components.dashboard.field', ['name' => 'alamat', 'label' => 'Alamat', 'value' => $lapangan->alamat, 'type' => 'textarea', 'required' => true])
    @include('components.dashboard.field', ['name' => 'deskripsi', 'label' => 'Deskripsi', 'value' => $lapangan->deskripsi, 'type' => 'textarea', 'required' => false])
    @include('components.dashboard.image-upload')
    <div class="akd-form-actions"><button class="akd-button" type="submit">Simpan perubahan</button><a href="{{ route('lapangan.index') }}">Batal</a><span role="status" data-management-status></span></div>
</form>
</div>
@endsection

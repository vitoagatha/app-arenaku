@extends('layouts.admin')
@section('main-content')
<div class="akd-management">
<div class="akd-page-heading"><a class="akd-back" href="{{ route('courts.index') }}">Kembali ke daftar</a><h1>Tambah court</h1><p>Lengkapi informasi berikut. Isian wajib ditandai pada label.</p></div>
@include('components.dashboard.feedback')
<form class="akd-surface akd-management-form" action="{{ route('courts.store') }}" method="POST" data-management-submit>
    @csrf
    @include('components.dashboard.field', ['name' => 'nama_courts', 'label' => 'Nama court', 'value' => '', 'type' => 'text', 'required' => true])
    <div class="akd-form-actions"><button class="akd-button" type="submit">Simpan data</button><a href="{{ route('courts.index') }}">Batal</a><span role="status" data-management-status></span></div>
</form>
</div>
@endsection

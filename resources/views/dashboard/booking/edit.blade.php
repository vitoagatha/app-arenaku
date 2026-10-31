@extends('layouts.admin')
@section('main-content')
<div class="akd-management">
<div class="akd-page-heading"><a class="akd-back" href="{{ route('booking.index') }}">Kembali ke daftar</a><h1>Edit data booking</h1><p>Lengkapi informasi berikut. Isian wajib ditandai pada label.</p></div>
@include('components.dashboard.feedback')
<form class="akd-surface akd-management-form" action="{{ route('booking.update', $booking->id) }}" method="POST" data-management-submit>
    @csrf
    @method('PUT')
    @include('components.dashboard.field', ['name' => 'nama', 'label' => 'Nama pemesan', 'value' => $booking->nama, 'type' => 'text', 'required' => true])
    @include('components.dashboard.field', ['name' => 'no_whatsapp', 'label' => 'Nomor WhatsApp', 'value' => $booking->no_whatsapp, 'type' => 'tel', 'required' => true])
    @include('components.dashboard.field', ['name' => 'durasi', 'label' => 'Durasi dalam jam', 'value' => $booking->durasi, 'type' => 'number', 'required' => true])
    <div class="akd-form-actions"><button class="akd-button" type="submit">Simpan perubahan</button><a href="{{ route('booking.index') }}">Batal</a><span role="status" data-management-status></span></div>
</form>
</div>
@endsection

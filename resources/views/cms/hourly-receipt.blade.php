@extends('layouts.cms')
@section('courtside', 'booking')
@section('title', 'Status booking | Arenaku')
@section('content')
<main id="main" class="ak-container ak-booking-page" tabindex="-1">
    <a class="ak-back" href="{{ route('cms.cek-booking', $lapangan->id) }}">Kembali ke jadwal venue</a>
    <section class="ak-panel" aria-labelledby="receipt-title">
        <h1 id="receipt-title">{{ ['pending' => 'Menunggu konfirmasi', 'confirmed' => 'Booking dikonfirmasi', 'expired' => 'Permintaan kedaluwarsa', 'canceled' => 'Booking dibatalkan'][$booking->status] }}</h1>
        <p>{{ $lapangan->nama_lapangan }} · {{ $booking->court_name }}</p>
        <p>{{ \Carbon\CarbonImmutable::parse($booking->starts_at, 'Asia/Jakarta')->locale('id')->translatedFormat('l, j F Y') }} · {{ substr($booking->starts_at, 11, 5) }}–{{ substr($booking->ends_at, 11, 5) }} WIB · {{ $booking->durasi }} jam</p>
        @if ($booking->status === 'pending')
            <p class="ak-notice">Menunggu konfirmasi pengelola</p>
        @elseif ($booking->status === 'confirmed')
            <p>Pengelola sudah menyetujui jadwal main. Konfirmasi ini tidak mencatat pembayaran.</p>
        @else
            <p>Buka jadwal venue untuk melihat slot yang masih tersedia.</p>
        @endif
        @if ($lapangan->booking_contact)
            <p>Kontak pengelola: <a href="tel:{{ preg_replace('/[^+0-9]/', '', $lapangan->booking_contact) }}">{{ $lapangan->booking_contact }}</a></p>
        @else
            <p>Kontak pengelola belum dicantumkan untuk venue ini.</p>
        @endif
        <a class="ak-button" href="{{ url()->current() }}">Perbarui status</a>
    </section>
</main>
@endsection

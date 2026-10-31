@extends('layouts.cms')
@section('courtside', 'booking')
@section('title', 'Booking tercatat | Arenaku')
@section('content')
<main id="main" class="ak-container ak-booking-page ak-result-page" tabindex="-1">
    @include('components.arenaku.booking-progress', ['step' => 3])
    <section class="ak-panel" aria-labelledby="result-title">
        <div class="ak-result-message" role="status"><i class="bi bi-check2-circle" aria-hidden="true"></i><h1 id="result-title">Booking berhasil dicatat</h1><p>Data bookingmu sudah tersimpan. Pencatatan ini bukan bukti pembayaran atau persetujuan booking.</p></div>
        <h2>Ringkasan booking</h2>
        @include('components.arenaku.booking-summary')
        <dl class="ak-summary">
            <div><dt>Nama pemesan</dt><dd>{{ $konfirmasiBooking->nama }}</dd></div>
            <div><dt>Nomor WhatsApp</dt><dd>{{ $konfirmasiBooking->no_whatsapp }}</dd></div>
            <div><dt>Durasi</dt><dd>{{ $konfirmasiBooking->durasi }} jam</dd></div>
        </dl>
        <a class="ak-button" href="{{ route('/') }}#hasil-lapangan">Kembali ke daftar lapangan</a>
    </section>
</main>
@endsection

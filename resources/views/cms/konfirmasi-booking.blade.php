@extends('layouts.cms')
@section('courtside', 'booking')
@section('title', 'Isi data booking | Arenaku')
@section('content')
<main id="main" class="ak-container ak-booking-page" tabindex="-1">
    <a class="ak-back" href="{{ route('cms.cek-booking', ['lapangan' => $lapangan->id, 'tanggal' => $jadwal->tanggal]) }}"><i class="bi bi-arrow-left" aria-hidden="true"></i>Kembali ke pilihan tanggal</a>
    @include('components.arenaku.booking-progress', ['step' => 2])
    <h1>Isi data booking</h1>
    <p class="ak-muted">Periksa jadwal pilihanmu, lalu lengkapi data pemesan.</p>
    <div class="ak-booking-grid">
        <section class="ak-panel" aria-labelledby="summary-title">
            <h2 id="summary-title">Jadwal pilihanmu</h2>
            @include('components.arenaku.booking-summary')
            <p class="ak-location"><i class="bi bi-geo-alt" aria-hidden="true"></i><span>{{ $lapangan->alamat }}</span></p>
        </section>
        <section class="ak-panel" aria-labelledby="customer-title">
            <h2 id="customer-title">Data pemesan</h2>
            <p class="ak-muted">Semua isian wajib diisi.</p>
            @include('components.arenaku.form-errors')
            <form class="ak-form" action="{{ route('booking.store') }}" method="POST" data-submit-feedback>
                @csrf
                <input type="hidden" name="jadwal_lapangan_id" id="jadwal_lapangan_id" value="{{ $jadwal->id }}">
                @error('jadwal_lapangan_id')<p class="ak-field-error">{{ $message }}</p>@enderror
                <div class="ak-field">
                    <label for="nama">Nama pemesan (wajib)</label>
                    <input type="text" id="nama" name="nama" value="{{ old('nama') }}" autocomplete="name" required @error('nama') aria-invalid="true" aria-describedby="nama-error" @enderror>
                    @error('nama')<p class="ak-field-error" id="nama-error">{{ $message }}</p>@enderror
                </div>
                <div class="ak-field">
                    <label for="no_whatsapp">Nomor WhatsApp (wajib)</label>
                    <input type="tel" id="no_whatsapp" name="no_whatsapp" value="{{ old('no_whatsapp') }}" autocomplete="tel" inputmode="tel" required aria-describedby="whatsapp-help @error('no_whatsapp') no_whatsapp-error @enderror" @error('no_whatsapp') aria-invalid="true" @enderror>
                    <p id="whatsapp-help" class="ak-help">Gunakan nomor WhatsApp yang dapat dihubungi.</p>
                    @error('no_whatsapp')<p class="ak-field-error" id="no_whatsapp-error">{{ $message }}</p>@enderror
                </div>
                <div class="ak-field">
                    <label for="durasi">Durasi dalam jam (wajib)</label>
                    <input type="number" id="durasi" name="durasi" value="{{ old('durasi') }}" required @error('durasi') aria-invalid="true" aria-describedby="durasi-error" @enderror>
                    @error('durasi')<p class="ak-field-error" id="durasi-error">{{ $message }}</p>@enderror
                </div>
                <p class="ak-help">Periksa nama, nomor WhatsApp, dan jadwal sebelum mengirim booking.</p>
                <button class="ak-button" type="submit" data-loading-label="Mengirim booking…">Kirim booking</button>
                <span class="ak-submit-status" role="status" aria-live="polite"></span>
            </form>
        </section>
    </div>
</main>
@endsection

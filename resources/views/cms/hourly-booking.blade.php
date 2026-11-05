@extends('layouts.cms')
@section('courtside', 'booking')
@section('title', 'Pilih jadwal per jam | Arenaku')
@section('content')
<main id="main" class="ak-container ak-booking-page" tabindex="-1">
    <a class="ak-back" href="{{ route('/') }}#hasil-lapangan">Kembali ke daftar lapangan</a>
    @include('components.arenaku.venue-detail')
    @include('components.arenaku.form-errors', ['linkErrors' => false])
    <section class="ak-panel" aria-labelledby="hourly-date-title">
        <h2 id="hourly-date-title">Pilih tanggal main</h2>
        <form class="ak-form ak-date-form" method="GET" action="{{ route('cms.cek-booking', $lapangan->id) }}">
            <div class="ak-field">
                <label for="tanggal">Tanggal main (WIB)</label>
                <input id="tanggal" name="tanggal" type="date" value="{{ $date }}" min="{{ $minimum }}" max="{{ $maximum }}" required>
            </div>
            <button class="ak-button" type="submit">Lihat jadwal</button>
        </form>
    </section>

    <form class="ak-form ak-hourly-form" method="POST" action="{{ route('booking.hourly.store', $lapangan->id) }}"
        data-submit-feedback data-public-hourly data-now="{{ $serverNow }}"
        data-availability="{{ route('booking.hourly.availability', ['lapangan' => $lapangan->id, 'tanggal' => $date]) }}">
        @csrf
        <input type="hidden" name="key" value="{{ old('key', $key) }}">
        <section class="ak-schedules" aria-labelledby="hourly-slots-title">
            <h2 id="hourly-slots-title">Jadwal {{ \Carbon\CarbonImmutable::parse($date, 'Asia/Jakarta')->locale('id')->translatedFormat('l, j F Y') }}</h2>
            <p>Pilih jam berurutan pada satu court. Semua jam menggunakan WIB.</p>
            @forelse ($courts as $court)
                <fieldset class="ak-court ak-hourly-court">
                    <legend>{{ $court->name }}</legend>
                    <ul class="ak-slot-list">
                        @forelse ($slots->get($court->id, []) as $slot)
                            @php($selected = in_array($slot['id'], (array) old('slot_ids', [])))
                            <li>
                                <label class="ak-slot {{ $slot['available'] ? 'ak-slot-ready' : 'ak-slot-unavailable' }}">
                                    <input type="checkbox" name="slot_ids[]" value="{{ $slot['id'] }}"
                                        data-court="{{ $court->id }}" data-court-name="{{ $court->name }}"
                                        data-start="{{ $slot['starts_at'] }}" data-end="{{ $slot['ends_at'] }}"
                                        data-available="{{ $slot['available'] ? '1' : '0' }}"
                                        @checked($selected) @disabled(!$slot['available'] && !$selected)>
                                    <strong>{{ substr($slot['starts_at'], 11, 5) }}–{{ substr($slot['ends_at'], 11, 5) }}</strong>
                                    <span data-slot-label>{{ $slot['label'] }}</span>
                                </label>
                            </li>
                        @empty
                            <li>Belum ada slot untuk court ini pada tanggal tersebut.</li>
                        @endforelse
                    </ul>
                </fieldset>
            @empty
                <p class="ak-notice">Belum ada court aktif di venue ini.</p>
            @endforelse
            <p class="ak-notice" role="status" tabindex="-1" data-selection-summary>Pilih slot untuk melihat ringkasan waktu main.</p>
            <p role="status" data-availability-feedback></p>
        </section>
        @if ($slots->isNotEmpty())
            <section class="ak-panel" aria-labelledby="hourly-customer-title">
                <h2 id="hourly-customer-title">Data pemesan</h2>
                <div class="ak-field">
                    <label for="nama">Nama pemesan</label>
                    <input id="nama" name="nama" autocomplete="name" value="{{ old('nama') }}" maxlength="255" required
                        @error('nama') aria-invalid="true" aria-describedby="hourly-name-error" @enderror>
                    @error('nama')<p id="hourly-name-error" class="ak-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="ak-field">
                    <label for="phone">Nomor WhatsApp</label>
                    <input id="phone" name="phone" type="tel" autocomplete="tel" value="{{ old('phone') }}" maxlength="30" required
                        @error('phone') aria-invalid="true" aria-describedby="hourly-phone-error" @enderror>
                    @error('phone')<p id="hourly-phone-error" class="ak-field-error">{{ $message }}</p>@enderror
                </div>
                <p>Slot ditahan maksimal 60 menit atau sampai jam mulai, mana yang lebih dahulu. Pengelola akan memeriksa permintaanmu.</p>
                <button class="ak-button" type="submit" data-loading-label="Mengirim permintaan…">Kirim permintaan booking</button>
                <span class="ak-submit-status" role="status"></span>
            </section>
        @endif
    </form>
</main>
<script src="{{ asset('js/arenaku-hourly-public.js') . '?v=' . filemtime(public_path('js/arenaku-hourly-public.js')) }}" defer></script>
@endsection

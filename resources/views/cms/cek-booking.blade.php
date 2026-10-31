@extends('layouts.cms')
@section('courtside', 'booking')
@section('title', 'Pilih jadwal | Arenaku')
@section('content')
<main id="main" class="ak-container ak-booking-page" tabindex="-1">
    <a class="ak-back" href="{{ route('/') }}#hasil-lapangan"><i class="bi bi-arrow-left" aria-hidden="true"></i>Kembali ke daftar lapangan</a>
    @include('components.arenaku.booking-progress', ['step' => 1])
    @include('components.arenaku.venue-detail')
    <section class="ak-panel" aria-labelledby="date-title">
        <h2 id="date-title">Tentukan tanggal main</h2>
        <p class="ak-muted">Pilih tanggal untuk melihat jadwal setiap court.</p>
        @include('components.arenaku.form-errors')
        <form class="ak-date-form ak-form" action="{{ route('lapangan.check-availability', $lapangan->id) }}" method="POST" data-submit-feedback>
            @csrf
            <div class="ak-field">
                <label for="tanggal">Tanggal main (wajib)</label>
                <input type="date" name="tanggal" id="tanggal" value="{{ old('tanggal', $tanggal ?? request('tanggal', '')) }}" required @error('tanggal') aria-invalid="true" aria-describedby="tanggal-error" @enderror>
                @error('tanggal')<p class="ak-field-error" id="tanggal-error">{{ $message }}</p>@enderror
            </div>
            <button class="ak-button" type="submit" data-loading-label="Memeriksa jadwal…">Lihat jadwal</button>
            <span class="ak-submit-status" role="status" aria-live="polite"></span>
        </form>
    </section>
    @if (isset($tanggal))
        <section id="hasil-tanggal" class="ak-schedules" data-scroll-result aria-labelledby="schedule-title">
            <div class="ak-section-heading"><div><h2 id="schedule-title">Jadwal lapangan</h2><p>{{ \Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('l, j F Y') }}</p></div></div>
            @forelse ($jadwalLapangan->groupBy('court_id') as $courtSchedules)
                <section class="ak-court" aria-labelledby="court-{{ $courtSchedules->first()->court_id }}">
                    <h3 id="court-{{ $courtSchedules->first()->court_id }}">{{ $courtSchedules->first()->court->nama_courts }}</h3>
                    <ul class="ak-slot-list">
                        @foreach ($courtSchedules as $jadwal)
                            @php($statusLabel = ['ready' => 'Tersedia', 'booking' => 'Dipesan', 'closed' => 'Ditutup'][$jadwal->status] ?? 'Tidak tersedia')
                            <li>
                                @if ($jadwal->status === 'ready')
                                    <a class="ak-slot ak-slot-ready" href="{{ route('lapangan.confirm-booking', ['id' => $jadwal->id]) }}" aria-label="Pilih {{ $jadwal->court->nama_courts }}, {{ $jadwal->jam_awal }} sampai {{ $jadwal->jam_akhir }}, {{ $tanggal }}">
                                        <strong>{{ $jadwal->jam_awal }} – {{ $jadwal->jam_akhir }}</strong><span>{{ $statusLabel }} · Pilih jadwal</span>
                                    </a>
                                @else
                                    <div class="ak-slot ak-slot-unavailable"><strong>{{ $jadwal->jam_awal }} – {{ $jadwal->jam_akhir }}</strong><span>{{ $statusLabel }}</span></div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @empty
                <div class="ak-empty"><h3>Belum ada jadwal pada tanggal ini</h3><p>Pilih tanggal lain, lalu tekan Lihat jadwal.</p><a class="ak-text-link" href="#tanggal">Ganti tanggal</a></div>
            @endforelse
            @if ($jadwalLapangan->isNotEmpty() && !$jadwalLapangan->contains('status', 'ready'))
                <p class="ak-notice">Tidak ada jadwal tersedia pada tanggal ini. Silakan pilih tanggal lain.</p>
            @endif
        </section>
    @endif
</main>
@endsection

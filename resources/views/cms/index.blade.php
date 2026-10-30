@extends('layouts.cms')
@section('courtside', 'homepage')
@section('content')
<main id="main" tabindex="-1">
    <section class="ak-hero ak-container" aria-labelledby="hero-title">
        <div class="ak-hero-copy">
            <p class="ak-intro"><span aria-hidden="true"></span> Ruang untuk main bareng</p>
            <h1 id="hero-title">Cari lapangan.<br>Tentukan waktu mainmu.</h1>
            <p>Mulai dari tempat yang cocok. Lihat jadwal lapangan, pilih waktu, lalu isi data booking.</p>
            <a class="ak-text-link" href="#hasil-lapangan">Jelajahi lapangan <i class="bi bi-arrow-down" aria-hidden="true"></i></a>
        </div>
        <div class="ak-hero-photo"><img src="{{ asset('assets/img/hero-bg.jpg') }}" alt="Lapangan bulu tangkis indoor dengan garis lapangan dan net" width="1600" height="960" fetchpriority="high"><span>Tempat bertemu. Waktu untuk bermain.</span></div>
    </section>
    <section class="ak-container ak-discovery" aria-label="Pencarian lapangan">
        <form class="ak-search" action="{{ route('cms.search') }}" method="POST" role="search">
            @csrf
            <div class="ak-search-field">
                <label for="search">Mau main di mana?</label>
                <div class="ak-input-wrap"><i class="bi bi-search" aria-hidden="true"></i><input type="search" name="search" id="search" placeholder="Cari nama lapangan" value="{{ old('search', request('search')) }}" autocomplete="off" aria-describedby="search-help"></div>
                <span id="search-help" class="ak-help">Cari berdasarkan nama lapangan.</span>
            </div>
            <button class="ak-button" type="submit">Cari lapangan <i class="bi bi-search" aria-hidden="true"></i></button>
        </form>
        <ol class="ak-booking-guide" aria-label="Cara booking"><li><i class="bi bi-geo-alt" aria-hidden="true"></i>Temukan lapangan</li><li><i class="bi bi-calendar3" aria-hidden="true"></i>Lihat jadwal</li><li><i class="bi bi-check2-square" aria-hidden="true"></i>Isi data booking</li></ol>
    </section>
    <section id="hasil-lapangan" class="ak-container ak-results" aria-labelledby="results-title" @if(request()->routeIs('cms.search')) data-scroll-result @endif>
        <div class="ak-section-heading"><div><h2 id="results-title">{{ request()->filled('search') ? 'Hasil pencarian lapangan' : 'Temukan tempat mainmu' }}</h2><p>{{ request()->filled('search') ? 'Pencarian: ' . request('search') : 'Kenali tempatnya, lalu lihat jadwal yang sesuai.' }}</p></div><span class="ak-result-count">{{ $lapangans->total() }} lapangan</span></div>
        @foreach (['success' => 'success', 'error' => 'danger', 'status' => 'success'] as $message => $type)
            @if (session($message))
                <div class="alert alert-{{ $type }} alert-dismissible fade show" role="{{ $message === 'error' ? 'alert' : 'status' }}">{{ session($message) }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup pemberitahuan"></button></div>
            @endif
        @endforeach
        <div class="ak-venue-list">
            @forelse ($lapangans as $lapangan)
                @include('components.arenaku.venue-card', ['lapangan' => $lapangan])
            @empty
                <div class="ak-empty"><i class="bi bi-search" aria-hidden="true"></i><h3>Belum ada lapangan yang ditemukan</h3><p>Coba nama lain atau kembali ke daftar untuk melihat lapangan yang tersedia.</p><a class="ak-button" href="{{ route('/') }}">Lihat semua lapangan</a></div>
            @endforelse
        </div>
        <div class="ak-pagination">{{ $lapangans->appends(request()->except('page'))->links() }}</div>
    </section>
</main>
@endsection

<section class="ak-venue-detail" aria-labelledby="venue-title">
    <div class="ak-detail-photo">
        @if ($lapangan->image)
            <img src="{{ asset('storage/' . $lapangan->image) }}" alt="Lapangan {{ $lapangan->nama_lapangan }}" width="640" height="440" data-venue-image>
        @endif
        <span class="ak-image-fallback" @if ($lapangan->image) hidden @endif>Foto lapangan belum tersedia</span>
    </div>
    <div>
        <h1 id="venue-title">{{ $lapangan->nama_lapangan }}</h1>
        <p class="ak-location"><i class="bi bi-geo-alt" aria-hidden="true"></i><span>{{ $lapangan->alamat }}</span></p>
        @if ($lapangan->deskripsi)
            <details class="ak-description"><summary>Informasi lapangan</summary><p>{{ $lapangan->deskripsi }}</p></details>
        @endif
    </div>
</section>

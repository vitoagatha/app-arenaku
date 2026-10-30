<article class="ak-venue" aria-labelledby="venue-{{ $lapangan->id }}">
    <div class="ak-venue-photo">
        @if ($lapangan->image)
            <img src="{{ asset('storage/' . $lapangan->image) }}" alt="Lapangan {{ $lapangan->nama_lapangan }}" width="640" height="440" loading="lazy" decoding="async" data-venue-image>
        @endif
        <span class="ak-image-fallback" @if ($lapangan->image) hidden @endif><i class="bi bi-image" aria-hidden="true"></i>Foto lapangan belum tersedia</span>
    </div>
    <div class="ak-venue-content">
        <h3 id="venue-{{ $lapangan->id }}">{{ $lapangan->nama_lapangan }}</h3>
        <p class="ak-location"><i class="bi bi-geo-alt" aria-hidden="true"></i><span>{{ $lapangan->alamat }}</span></p>
        @if ($lapangan->deskripsi)<p class="ak-venue-description">{{ \Illuminate\Support\Str::limit($lapangan->deskripsi, 190) }}</p>@endif
        <div class="ak-venue-action"><span>Pilih tanggal untuk melihat jadwal.</span><a class="ak-button" href="{{ route('cms.cek-booking', ['lapangan' => $lapangan->id]) }}" aria-label="Lihat jadwal {{ $lapangan->nama_lapangan }}">Lihat jadwal <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
    </div>
</article>

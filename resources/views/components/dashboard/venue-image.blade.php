<span class="akd-image-wrap">
    @if($lapangan->image)<img class="akd-venue-image" src="{{ asset('storage/' . $lapangan->image) }}" alt="Foto {{ $lapangan->nama_lapangan }}" width="120" height="80" loading="lazy" data-management-image>@endif
    <span class="akd-image-placeholder" @if($lapangan->image) hidden @endif>Foto belum tersedia</span>
</span>

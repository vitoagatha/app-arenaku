<dl class="ak-summary">
    <div><dt>Lapangan</dt><dd>{{ $lapangan->nama_lapangan }}</dd></div>
    <div><dt>Court</dt><dd>{{ $jadwal->court->nama_courts }}</dd></div>
    <div><dt>Tanggal</dt><dd>{{ \Carbon\Carbon::parse($jadwal->tanggal)->locale('id')->translatedFormat('l, j F Y') }}</dd></div>
    <div><dt>Jam</dt><dd>{{ substr($jadwal->jam_awal, 0, 5) }} – {{ substr($jadwal->jam_akhir, 0, 5) }}</dd></div>
</dl>

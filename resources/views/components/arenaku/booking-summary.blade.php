<dl class="ak-summary">
    <div><dt>Lapangan</dt><dd>{{ $lapangan->nama_lapangan }}</dd></div>
    <div><dt>Court</dt><dd>{{ $jadwal->court->nama_courts }}</dd></div>
    <div><dt>Tanggal</dt><dd>{{ \Carbon\Carbon::parse($jadwal->tanggal)->locale('id')->translatedFormat('l, j F Y') }}</dd></div>
    <div><dt>Jam</dt><dd>{{ $jadwal->jam_awal }} – {{ $jadwal->jam_akhir }}</dd></div>
</dl>

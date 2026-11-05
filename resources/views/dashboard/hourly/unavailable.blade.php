@extends('layouts.admin')
@section('main-content')
<div class="akd-management"><div class="akd-page-heading"><a href="{{ route('jadwal.index') }}">Kembali ke pilihan lapangan</a><h1>Operasional {{ $lapangan->nama_lapangan }}</h1></div>
<section class="akd-surface"><h2>Pengaturan per jam sedang disiapkan</h2><p>Fitur ini tersedia setelah persiapan database dan verifikasi lapangan selesai. Hubungi admin untuk kesiapan venue.</p><a class="btn btn-secondary" href="{{ route('jadwal.court', $lapangan->id) }}">Lihat jadwal yang sudah tercatat</a></section></div>
@endsection

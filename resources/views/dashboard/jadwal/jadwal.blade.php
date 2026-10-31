@extends('layouts.admin')
@section('main-content')
<div class="akd-management" data-schedule-page data-old-values="{{ json_encode(old()) }}" data-has-errors="{{ $errors->any() ? 'true' : 'false' }}">
    <div class="akd-page-heading"><a class="akd-back" href="{{ route('jadwal.index') }}">Kembali ke pilihan lapangan</a><h1>Jadwal {{ $lapangan->nama_lapangan }}</h1><p>Kelola tanggal, jam main, dan status setiap court.</p></div>
    @include('components.dashboard.feedback', ['linkErrors' => false])
    <section class="akd-surface">
        <div class="akd-panel-heading"><h2>Daftar jadwal</h2><button class="btn btn-primary" type="button" data-toggle="modal" data-target="#addModal">Tambah jadwal</button></div>
        @if($courts->isEmpty())<p class="akd-note">Belum ada court yang dapat dipilih untuk membuat jadwal.</p>@endif
        @if($jadwalLapangan->isEmpty())<p class="akd-note" role="status">Belum ada jadwal untuk lapangan ini. Gunakan Tambah jadwal untuk mulai mengisi tanggal dan jam main.</p>@endif
        @component('components.dashboard.management-table', ['caption' => 'Jadwal lapangan ' . $lapangan->nama_lapangan, 'headers' => ['No', 'Court', 'Jam', 'Tanggal', 'Status', 'Aksi']])
            @foreach($jadwalLapangan as $jadwal)
                <tr><td>{{ $loop->iteration }}</td><td>{{ $jadwal->court->nama_courts }}</td><td class="akd-nowrap">{{ $jadwal->jam_awal }} – {{ $jadwal->jam_akhir }}</td><td data-order="{{ $jadwal->tanggal }}">{{ $jadwal->tanggal }}</td><td>@include('components.dashboard.status', ['status' => $jadwal->status])</td><td><div class="akd-row-actions"><button class="btn btn-secondary btn-sm" type="button" data-toggle="modal" data-target="#editModal{{ $jadwal->id }}" aria-label="Edit jadwal {{ $jadwal->court->nama_courts }}, {{ $jadwal->tanggal }}, {{ $jadwal->jam_awal }}">Edit</button>@include('components.dashboard.delete-action', ['action' => route('jadwal.destroy', $jadwal->id), 'label' => 'jadwal ' . $jadwal->court->nama_courts . ', ' . $jadwal->tanggal . ', ' . $jadwal->jam_awal])</div></td></tr>
            @endforeach
        @endcomponent
    </section>
</div>
@include('components.dashboard.schedule-modal')
@foreach($jadwalLapangan as $jadwal) @include('components.dashboard.schedule-modal', ['schedule' => $jadwal]) @endforeach
@endsection

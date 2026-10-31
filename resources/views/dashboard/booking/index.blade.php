@extends('layouts.admin')

@section('main-content')
<div class="akd-management">
<div class="akd-page-heading"><h1>Daftar booking</h1><p>Periksa data pemesan, lapangan, dan waktu booking.</p></div>
@include('components.dashboard.feedback')
@if($bookings->isEmpty())<p class="akd-note" role="status">Belum ada data pada daftar ini.</p>@endif
<div class="row">
        <div class="col-xl-12 col-md-12 mb-4">
            <div class="card akd-management-panel mb-4">
                <div class="card-header py-3 d-flex justify-content-between">
                    <h2 class="m-0 font-weight-bold text-primary">Daftar Booking</h2>
                </div>
                <div class="card-body">
                    <p class="akd-muted">Cari data atau geser tabel ke samping pada layar kecil.</p><div class="table-responsive" role="region" aria-label="Daftar booking" tabindex="0">
                        <table class="table akd-table" id="dataTable" width="100%" cellspacing="0">
                            <caption class="sr-only">Daftar booking</caption><thead>
                                <tr>
                                    <th scope="col">No</th>
                                    <th scope="col">Nama</th>
                                    <th scope="col">Nomor WhatsApp</th>
                                    <th scope="col">Durasi</th>
                                    <th scope="col">Lapangan</th>
                                    <th scope="col">Court</th>
                                    <th scope="col">Jam</th>
                                    <th scope="col">Tanggal</th>
                                    <th scope="col">Status</th><th scope="col">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $no = 1 @endphp
                                @foreach ($bookings as $booking)
                                    <tr>
                                        <td>{{ $no++ }}</td>
                                        <td>{{ $booking->nama }}</td>
                                        <td>{{ $booking->no_whatsapp }}</td>
                                        <td>{{ $booking->durasi }}</td>
                                        <td>{{ $booking->jadwalLapangan->lapangan->nama_lapangan }}</td>
                                        <td>{{ $booking->jadwalLapangan->court->nama_courts }}</td>
                                        <td>{{ $booking->jadwalLapangan->jam_awal }} -
                                            {{ $booking->jadwalLapangan->jam_akhir }}</td>
                                        <td data-order="{{ $booking->jadwalLapangan->tanggal }}">{{ \Carbon\Carbon::parse($booking->jadwalLapangan->tanggal)->locale('id')->translatedFormat('j M Y') }}</td>
                                        <td>@include('components.dashboard.status', ['status' => $booking->status, 'kind' => 'booking'])</td><td>
                                            <a href="{{ route('booking.edit', ['booking' => $booking->id]) }}"
                                                class="btn btn-primary btn-sm" aria-label="Edit {{ $booking->nama }}">Edit</a>
                                            @include('components.dashboard.delete-action', ['action' => route('booking.destroy', $booking->id), 'label' => $booking->nama])
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

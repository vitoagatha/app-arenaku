@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">{{ __('Booking') }}</h1>

    @if (session('success'))
        <div class="alert alert-success border-left-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @elseif (session('error'))
        <div class="alert alert-danger border-left-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('status'))
        <div class="alert alert-success border-left-success" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <div class="row">
        <div class="col-xl-12 col-md-12 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Booking</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Nomor WhatsApp</th>
                                    <th>Durasi</th>
                                    <th>Lapangan</th>
                                    <th>Court</th>
                                    <th>Jam</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
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
                                        <td>{{ $booking->jadwalLapangan->tanggal }}</td>
                                        <td>
                                            <a href="{{ route('booking.edit', ['booking' => $booking->id]) }}"
                                                class="btn btn-primary btn-sm">Edit</a>
                                            <form action="{{ route('booking.destroy', ['booking' => $booking->id]) }}"
                                                method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                            </form>
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
@endsection

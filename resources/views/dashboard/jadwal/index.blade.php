@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">{{ __('Lapangan') }}</h1>

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
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Lapangan</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Lapangan</th>
                                    <th>Alamat</th>
                                    <th>Deskripsi</th>
                                    <th>Gambar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $no = 1 @endphp
                                @foreach ($lapangans as $lapangan)
                                    <tr>
                                        <td>{{ $no++ }}</td>
                                        <td>{{ $lapangan->nama_lapangan }}</td>
                                        <td>{{ $lapangan->alamat }}</td>
                                        <td>{{ $lapangan->deskripsi }}</td>
                                        <td><img src="{{ asset('storage/' . $lapangan->image) }}"
                                                alt="Gambar {{ $lapangan->nama_lapangan }}" width="50"></td>
                                        <td>
                                            <a href="{{ route('jadwal.court', $lapangan->id) }}"
                                                class="btn btn-info btn-sm">
                                                <i class="far fa-calendar-check"></i>
                                            </a>
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

@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">{{ __('Jadwal Lapangan') }}</h1>

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
                    <h6 class="m-0 font-weight-bold text-primary">Data Jadwal Lapangan {{ $lapangan->nama_lapangan }}</h6>
                    <div>
                        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal">Tambah
                            Jadwal</button>
                        <a href="{{ route('jadwal.index') }}" class="btn btn-sm btn-secondary">Kembali</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Court</th>
                                    <th>Jam</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $no = 1 @endphp
                                @foreach ($jadwalLapangan as $jadwal)
                                    <tr>
                                        <td>{{ $no++ }}</td>
                                        <td>{{ $jadwal->court->nama_courts }}</td>
                                        <td>{{ $jadwal->jam_awal }} - {{ $jadwal->jam_akhir }}</td>
                                        <td>{{ $jadwal->tanggal }}</td>
                                        <td>{{ $jadwal->status }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" data-toggle="modal"
                                                data-target="#editModal{{ $jadwal->id }}">Edit</button>
                                            <form action="{{ route('jadwal.destroy', $jadwal->id) }}" method="POST"
                                                style="display: inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
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

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">Tambah Jadwal</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('jadwal.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="lapangan_id" value="{{ $lapangan->id }}">
                        <div class="form-group">
                            <label for="court_id">Court</label>
                            <select class="form-control" id="court_id" name="court_id" required>
                                @foreach ($courts as $court)
                                    <option value="{{ $court->id }}">{{ $court->nama_courts }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="jam_awal">Jam Awal</label>
                            <input type="time" class="form-control" id="jam_awal" name="jam_awal" required>
                        </div>
                        <div class="form-group">
                            <label for="jam_akhir">Jam Akhir</label>
                            <input type="time" class="form-control" id="jam_akhir" name="jam_akhir" required>
                        </div>
                        <div class="form-group">
                            <label for="tanggal">Tanggal</label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    @foreach ($jadwalLapangan as $jadwal)
        <div class="modal fade" id="editModal{{ $jadwal->id }}" tabindex="-1" role="dialog"
            aria-labelledby="editModalLabel{{ $jadwal->id }}" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel{{ $jadwal->id }}">Edit Jadwal</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('jadwal.update', $jadwal->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <input type="hidden" name="lapangan_id" value="{{ $lapangan->id }}">
                            <div class="form-group">
                                <label for="court_id">Court</label>
                                <select class="form-control" id="court_id" name="court_id" required>
                                    @foreach ($courts as $court)
                                        <option value="{{ $court->id }}"
                                            {{ $jadwal->court_id == $court->id ? 'selected' : '' }}>
                                            {{ $court->nama_courts }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="jam_awal">Jam Awal</label>
                                <input type="time" class="form-control" id="jam_awal" name="jam_awal"
                                    value="{{ $jadwal->jam_awal }}" required>
                            </div>
                            <div class="form-group">
                                <label for="jam_akhir">Jam Akhir</label>
                                <input type="time" class="form-control" id="jam_akhir" name="jam_akhir"
                                    value="{{ $jadwal->jam_akhir }}" required>
                            </div>
                            <div class="form-group">
                                <label for="tanggal">Tanggal</label>
                                <input type="date" class="form-control" id="tanggal" name="tanggal"
                                    value="{{ $jadwal->tanggal }}" required>
                            </div>
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control text-capitalize" id="status" name="status" required>
                                    @php $status = ['ready', 'booking', 'closed'] @endphp
                                    @foreach ($status as $status)
                                        <option value="{{ $status }}"
                                            {{ $jadwal->status == $status ? 'selected' : '' }}>
                                            {{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection

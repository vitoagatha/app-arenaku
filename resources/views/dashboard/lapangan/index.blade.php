@extends('layouts.admin')

@section('main-content')
<div class="akd-management">
<div class="akd-page-heading"><h1>Daftar lapangan</h1><p>Kelola informasi dan foto tempat olahraga.</p></div>
@include('components.dashboard.feedback')
@if($lapangans->isEmpty())<p class="akd-note" role="status">Belum ada data pada daftar ini.</p>@endif
<div class="row">
        <div class="col-xl-12 col-md-12 mb-4">
            <div class="card akd-management-panel mb-4">
                <div class="card-header py-3 d-flex justify-content-between">
                    <h2 class="m-0 font-weight-bold text-primary">Daftar Lapangan</h2>
                    <a href="{{ route('lapangan.create') }}" class="btn btn-primary btn-sm">Tambah lapangan</a>
                </div>
                <div class="card-body">
                    <p class="akd-muted">Cari data atau geser tabel ke samping pada layar kecil.</p><div class="table-responsive" role="region" aria-label="Daftar lapangan" tabindex="0">
                        <table class="table akd-table" id="dataTable" width="100%" cellspacing="0">
                            <caption class="sr-only">Daftar lapangan</caption><thead>
                                <tr>
                                    <th scope="col">No</th>
                                    <th scope="col">Nama Lapangan</th>
                                    <th scope="col">Alamat</th>
                                    <th scope="col">Deskripsi</th>
                                    <th scope="col">Gambar</th>
                                    <th scope="col">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $no = 1 @endphp
                                @foreach ($lapangans as $lapangan)
                                    <tr>
                                        <td>{{ $no++ }}</td>
                                        <td>{{ $lapangan->nama_lapangan }}</td>
                                        <td>{{ $lapangan->alamat }}</td>
                                        <td><div class="akd-cell-description">{{ $lapangan->deskripsi }}</div></td>
                                        <td>@include('components.dashboard.venue-image')</td>
                                        <td>
                                            <a href="{{ route('lapangan.edit', $lapangan->id) }}"
                                                class="btn btn-primary btn-sm" aria-label="Edit {{ $lapangan->nama_lapangan }}">Edit</a>
                                            @include('components.dashboard.delete-action', ['action' => route('lapangan.destroy', $lapangan->id), 'label' => $lapangan->nama_lapangan])
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

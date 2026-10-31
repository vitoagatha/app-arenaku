@extends('layouts.admin')

@section('main-content')
<div class="akd-management">
<div class="akd-page-heading"><h1>Data court</h1><p>Kelola unit court untuk penjadwalan.</p></div>
@include('components.dashboard.feedback')
@if($courts->isEmpty())<p class="akd-note" role="status">Belum ada data pada daftar ini.</p>@endif
<div class="row">
        <div class="col-xl-12 col-md-12 mb-4">
            <div class="card akd-management-panel mb-4">
                <div class="card-header py-3 d-flex justify-content-between">
                    <h2 class="m-0 font-weight-bold text-primary">Daftar Courts</h2>
                    <a href="{{ route('courts.create') }}" class="btn btn-primary btn-sm">Tambah court</a>
                </div>
                <div class="card-body">
                    <p class="akd-muted">Cari data atau geser tabel ke samping pada layar kecil.</p><div class="table-responsive" role="region" aria-label="Data court" tabindex="0">
                        <table class="table akd-table" id="dataTable" width="100%" cellspacing="0">
                            <caption class="sr-only">Data court</caption><thead>
                                <tr>
                                    <th scope="col">No</th>
                                    <th scope="col">Nama court</th>
                                    <th scope="col">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $no = 1 @endphp
                                @foreach ($courts as $court)
                                    <tr>
                                        <td>{{ $no++ }}</td>
                                        <td>{{ $court->nama_courts }}</td>
                                        <td>
                                            <a href="{{ route('courts.edit', $court->id) }}"
                                                class="btn btn-primary btn-sm" aria-label="Edit {{ $court->nama_courts }}">Edit</a>
                                            @include('components.dashboard.delete-action', ['action' => route('courts.destroy', $court->id), 'label' => $court->nama_courts])
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

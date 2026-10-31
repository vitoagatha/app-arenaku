@extends('layouts.admin')

@section('main-content')
<div class="akd-management">
<div class="akd-page-heading"><h1>Pengguna</h1><p>Kelola data akun dan peran pengguna.</p></div>
@include('components.dashboard.feedback')
@if($users->isEmpty())<p class="akd-note" role="status">Belum ada data pada daftar ini.</p>@endif
<div class="row">
        <div class="col-xl-12 col-md-12 mb-4">
            <div class="card akd-management-panel mb-4">
                <div class="card-header py-3 d-flex justify-content-between">
                    <h2 class="m-0 font-weight-bold text-primary">Daftar Pengguna</h2>
                    <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">Tambah pengguna</a>
                </div>
                <div class="card-body">
                    <p class="akd-muted">Cari data atau geser tabel ke samping pada layar kecil.</p><div class="table-responsive" role="region" aria-label="Pengguna" tabindex="0">
                        <table class="table akd-table" id="dataTable" width="100%" cellspacing="0">
                            <caption class="sr-only">Pengguna</caption><thead>
                                <tr>
                                    <th scope="col">No</th>
                                    <th scope="col">Nama Depan</th>
                                    <th scope="col">Nama Belakang</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">No Handphone</th>
                                    <th scope="col">Role</th>
                                    <th scope="col">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $no = 1 @endphp
                                @foreach ($users as $user)
                                    <tr>
                                        <td>{{ $no++ }}</td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->last_name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->no_handphone ?? '-' }}</td>
                                        <td>{{ ucfirst($user->role) }}</td>
                                        <td>
                                            <a href="{{ route('users.edit', $user->id) }}"
                                                class="btn btn-primary btn-sm" aria-label="Edit {{ $user->name }}">Edit</a>
                                            @include('components.dashboard.delete-action', ['action' => route('users.destroy', $user->id), 'label' => $user->name])
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

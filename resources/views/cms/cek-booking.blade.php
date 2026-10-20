<!-- resources/views/cms/cek-booking.blade.php -->

@extends('layouts.cms')

@section('content')
    <main id="main">
        <section id="about" class="about" style="padding-top: 100px">
            <div class="container mt-3">
                <div class="row">
                    <div class="col-lg-6 pt-3">
                        <img src="{{ asset('storage/' . $lapangan->image) }}" class="img-fluid rounded" alt="Lapangan Image">
                    </div>

                    <div class="col-lg-6 pt-3 content">
                        <h3>{{ $lapangan->nama_lapangan }}</h3>
                        <p class="fst-italic">
                            {{ $lapangan->alamat }}
                        </p>
                        <p>
                            {{ $lapangan->deskripsi }}
                        </p>

                        <div class="col-lg-12 pt-3">
                            <div class="card">
                                <div class="card-header h3 fw-bold text-center">Booking?</div>
                                <div class="card-body">
                                    <form action="{{ route('lapangan.check-availability', $lapangan->id) }}" method="POST"
                                        class="row g-3">
                                        @csrf
                                        <div class="col-md-12">
                                            <label for="tanggal" class="form-label">Tanggal</label>
                                            <input type="date" class="form-control" name="tanggal" id="tanggal"
                                                required>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">Cek Ketersediaan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @if (isset($tanggal))
            <section id="about" class="about">
                <div class="container mt-1">
                    <div class="d-flex justify-content-center h3 py-5">Jam kosong hari ini</div>
                    @foreach ($jadwalLapangan as $jadwal)
                        <div class="d-flex justify-content-center">
                            <div class="card mb-3" style="max-width: 540px;">
                                <div class="row g-0">
                                    <div class="col-md-4 p-3" style="background-color: #8B8B8B">
                                        <img src="{{ asset('assets/img/img5.png') }}" class="img-fluid rounded-start"
                                            alt="...">
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body">
                                            <h5 class="card-title fw-bold">{{ $jadwal->court->nama_courts }}</h5>
                                            <p class="card-text">
                                                Jam : {{ $jadwal->jam_awal }} - {{ $jadwal->jam_akhir }} <br>
                                                Tanggal : {{ $jadwal->tanggal }}
                                            </p>
                                            <p class="card-text">
                                                @if ($jadwal->status === 'ready')
                                                    <p>
                                                        <a href="{{ route('lapangan.confirm-booking', ['id' => $jadwal->id]) }}"
                                                            class="btn btn-primary btn-sm rounded">Pesan
                                                            Lapangan!</a>
                                                    </p>
                                                @else
                                                    <p>
                                                        <button type="button"
                                                            class="btn btn-dark btn-sm rounded">{{ $jadwal->status }}!</button>
                                                    </p>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </main>
@endsection

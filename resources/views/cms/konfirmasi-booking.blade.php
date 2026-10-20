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
                    </div>
                </div>
            </div>
        </section>

        <div class="about">
            <div class="container mb-5">
                <div class="row">
                    <div class="col-12 p-3">
                        <div class="card mt-3">
                            <div class="card-header h3 fw-bold text-center">Konfirmasi Booking</div>
                            <div class="card-body m-5">
                                <div class="row">
                                    <div class="col-6">
                                        <form action="{{ route('booking.store') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="jadwal_lapangan_id" value="{{ $jadwal->id }}">
                                            <div class="mb-3">
                                                <label for="nama" class="form-label">Nama</label>
                                                <input type="text" class="form-control" id="nama" name="nama"
                                                    autocomplete="off" placeholder="Atas nama ?" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="no_whatsapp" class="form-label">Nomor WhatsApp</label>
                                                <input type="number" class="form-control" id="no_whatsapp"
                                                    name="no_whatsapp" placeholder="Nomor" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="durasi" class="form-label">Durasi (Jam)</label>
                                                <input type="number" class="form-control" id="durasi" name="durasi"
                                                    placeholder="Jam" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Pesan!</button>
                                        </form>
                                    </div>
                                    <div class="col-6">
                                        <h5 class="card-title fw-bold">{{ $jadwal->court->nama_courts }}</h5>
                                        <p class="card-text">
                                            Jam : {{ $jadwal->jam_awal }} - {{ $jadwal->jam_akhir }} <br>
                                            Tanggal : {{ $jadwal->tanggal }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

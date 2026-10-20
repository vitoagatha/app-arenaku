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
                            <div class="card-header h3 fw-bold text-center">Booking Sukses</div>
                            <div class="card-body m-5">
                                <div class="row">
                                    <div class="col-12 text-center">
                                        <h5 class="card-title fw-bold">{{ $jadwal->court->nama_courts }}</h5>
                                        <p class="card-text">
                                            Jam : {{ $jadwal->jam_awal }} - {{ $jadwal->jam_akhir }} <br>
                                            Tanggal : {{ $jadwal->tanggal }} <br>
                                            Durasi : {{ $konfirmasiBooking->durasi }} Jam <br>
                                            Nama : {{ $konfirmasiBooking->nama }} <br>
                                            Nomor WhatsApp : {{ $konfirmasiBooking->no_whatsapp }}
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

@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">{{ __('Edit Booking') }}</h1>

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
                    <h6 class="m-0 font-weight-bold text-primary">Form Edit Booking</h6>
                    <a href="{{ route('booking.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
                </div>
                <div class="card-body">
                    <form action="{{ route('booking.update', ['booking' => $booking->id]) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="nama">Nama</label>
                            <input type="text" class="form-control" id="nama" name="nama"
                                value="{{ $booking->nama }}" required>
                        </div>
                        <div class="form-group">
                            <label for="no_whatsapp">Nomor WhatsApp</label>
                            <input type="text" class="form-control" id="no_whatsapp" name="no_whatsapp"
                                value="{{ $booking->no_whatsapp }}" required>
                        </div>
                        <div class="form-group">
                            <label for="durasi">Durasi (Jam)</label>
                            <input type="number" class="form-control" id="durasi" name="durasi"
                                value="{{ $booking->durasi }}" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

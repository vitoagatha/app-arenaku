@extends('layouts.cms')

@section('content')
    <main id="main">
        <section id="about" class="about">
            <div class="container rounded mt-3">
                <form action="{{ route('cms.search') }}" method="POST">
                    @csrf
                    <div class="row p-3">
                        <div class="col-8">
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="Cari Lapangan?" autocomplete="off">
                        </div>
                        <div class="col">
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <section id="hasil-lapangan" class="about"
            @if(request()->routeIs('cms.search')) data-scroll-result @endif>
            <div class="d-flex justify-content-center">
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
            </div>

            @if (session('status'))
                <div class="alert alert-success border-left-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            @foreach ($lapangans as $lapangan)
                <div class="container mb-4 rounded" style="background-color: #F2F4FB">
                    <div class="row">
                        <div class="col-lg-3 pt-3">
                            <img src="{{ asset('storage/' . $lapangan->image) }}" class="img-fluid rounded" alt=""
                                style="width: 250px">
                        </div>

                        <div class="col-lg-6 pt-3 content">
                            <h3>{{ $lapangan->nama_lapangan }}</h3>
                            <p class="fst-italic">
                                {{ $lapangan->alamat }}
                            </p>
                            <p>
                                {{ $lapangan->deskripsi }}
                            </p>
                            <p>
                                <a href="{{ route('cms.cek-booking', ['lapangan' => $lapangan->id]) }}"
                                    class="btn btn-primary btn-lg rounded">Booking Sekarang!</a>
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="d-flex justify-content-center bg-transparent">
                {{ $lapangans->appends(request()->except('page'))->links() }}
            </div>
        </section>
    </main>
@endsection

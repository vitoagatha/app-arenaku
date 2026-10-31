@php
    $editing = isset($schedule);
    $modalId = $editing ? 'editModal' . $schedule->id : 'addModal';
    $prefix = $modalId . '-';
@endphp
<div class="modal fade akd-management" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-labelledby="{{ $modalId }}-title" aria-hidden="true">
    <div class="modal-dialog" role="document"><div class="modal-content">
        <div class="modal-header"><h2 class="modal-title h5" id="{{ $modalId }}-title">{{ $editing ? 'Edit jadwal' : 'Tambah jadwal' }}</h2><button type="button" class="close" data-dismiss="modal" aria-label="Tutup formulir"><span aria-hidden="true">&times;</span></button></div>
        <form action="{{ $editing ? route('jadwal.update', $schedule->id) : route('jadwal.store') }}" method="POST" data-management-submit data-schedule-form>
            @csrf
            @if($editing) @method('PUT') @endif
            <div class="modal-body"><p class="akd-muted">{{ $lapangan->nama_lapangan }}. Semua isian wajib diisi.</p>
                <input type="hidden" name="lapangan_id" value="{{ $lapangan->id }}">
                @include('components.dashboard.field', ['name' => 'court_id', 'label' => 'Court', 'type' => 'select', 'options' => $courts->pluck('nama_courts', 'id'), 'value' => $schedule->court_id ?? $courts->first()->id ?? '', 'required' => true, 'prefix' => $prefix, 'useOld' => !$editing && old('_method') !== 'PUT'])
                @include('components.dashboard.field', ['name' => 'jam_awal', 'label' => 'Jam awal', 'type' => 'time', 'value' => $schedule->jam_awal ?? '', 'required' => true, 'prefix' => $prefix, 'useOld' => !$editing && old('_method') !== 'PUT'])
                @include('components.dashboard.field', ['name' => 'jam_akhir', 'label' => 'Jam akhir', 'type' => 'time', 'value' => $schedule->jam_akhir ?? '', 'required' => true, 'prefix' => $prefix, 'useOld' => !$editing && old('_method') !== 'PUT'])
                @include('components.dashboard.field', ['name' => 'tanggal', 'label' => 'Tanggal', 'type' => 'date', 'value' => $schedule->tanggal ?? '', 'required' => true, 'prefix' => $prefix, 'useOld' => !$editing && old('_method') !== 'PUT'])
                @if($editing)
                    @include('components.dashboard.field', ['name' => 'status', 'label' => 'Status jadwal', 'type' => 'select', 'options' => ['ready' => 'Tersedia', 'booking' => 'Dipesan', 'closed' => 'Ditutup'], 'value' => $schedule->status, 'required' => true, 'prefix' => $prefix, 'useOld' => false])
                @endif
                <span role="status" data-management-status></span>
            </div>
            <div class="modal-footer"><button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan jadwal</button></div>
        </form>
    </div></div>
</div>

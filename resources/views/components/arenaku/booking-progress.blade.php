<ol class="ak-progress" aria-label="Tahap booking">
    @foreach (['Pilih jadwal', 'Data booking', 'Hasil pengiriman'] as $label)
        <li @if ($loop->iteration === $step) aria-current="step" @endif><span aria-hidden="true">{{ $loop->iteration }}</span>{{ $label }}</li>
    @endforeach
</ol>

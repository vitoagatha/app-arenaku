@php
    $labels = ($kind ?? 'schedule') === 'booking' ? ['pending' => 'Menunggu', 'confirmed' => 'Dikonfirmasi', 'canceled' => 'Dibatalkan'] : ['ready' => 'Tersedia', 'booking' => 'Dipesan', 'closed' => 'Ditutup'];
    $tone = in_array($status, ['ready', 'confirmed'], true) ? 'available' : (in_array($status, ['closed', 'canceled'], true) ? 'closed' : 'waiting');
@endphp
<span class="akd-status akd-status-{{ $tone }}">{{ $labels[$status] ?? ($status ?: 'Tidak tercatat') }}</span>

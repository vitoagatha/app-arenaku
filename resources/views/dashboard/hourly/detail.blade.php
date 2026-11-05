@extends('layouts.admin')
@section('main-content')
<div class="akd-management"><div class="akd-page-heading"><a href="{{ route('booking.index') }}">Kembali ke daftar booking</a><h1>Booking #{{ $row->id }}</h1><p>{{ $row->nama_lapangan }} · {{ $row->court_name ?? 'Riwayat court lama' }}</p></div>
@include('components.dashboard.feedback',['linkErrors'=>false])
<section class="akd-surface"><h2>Data pemesanan</h2><dl class="akd-booking-facts"><div><dt>Pemesan</dt><dd>{{ $row->nama }}</dd></div><div><dt>Kontak</dt><dd>{{ $row->no_whatsapp }}</dd></div><div><dt>Durasi tercatat</dt><dd>{{ $row->durasi }} jam</dd></div><div><dt>Status</dt><dd>@include('components.dashboard.status',['status'=>$row->status,'kind'=>'booking'])</dd></div>
@if(!$row->is_legacy)<div><dt>Waktu bermain (WIB)</dt><dd>{{ substr($row->starts_at,0,10) }}<br>{{ substr($row->starts_at,11,5) }}–{{ substr($row->ends_at,11,5) }} WIB</dd></div><div><dt>Sumber</dt><dd>{{ ['website'=>'Website','walk_in'=>'Datang langsung','whatsapp'=>'WhatsApp'][$row->source] ?? 'Tidak tercatat' }}</dd></div>@endif</dl>
@if($row->is_legacy)<p>Jadwal tercatat: {{ $row->legacy_date }}, {{ substr($row->jam_awal,0,5) }}–{{ substr($row->jam_akhir,0,5) }} WIB.</p><p class="akd-note">Riwayat lama dipertahankan sesuai data asli. Konfirmasi dan pembatalan alur baru tidak diterapkan pada record ini.</p>
@else
<h3>Slot yang dicatat</h3><ul>@foreach($slots as $slot)<li>{{ substr($slot->starts_at,0,16) }}–{{ substr($slot->ends_at,11,5) }} WIB</li>@endforeach</ul>
@if($row->status==='pending')<p class="akd-note">Konfirmasi sebelum <time datetime="{{ str_replace(' ','T',$row->expires_at) }}+07:00">{{ $row->expires_at }} WIB</time>. Tenggat ini tidak diperpanjang ketika halaman dibuka ulang.</p><form method="POST" action="{{ route('booking.transition',$row->id) }}" data-management-submit>@csrf<input type="hidden" name="action" value="confirm"><button class="btn btn-primary" type="submit">Konfirmasi booking #{{ $row->id }}</button><span data-management-status role="status"></span></form>@endif
<p class="akd-muted">Dikonfirmasi bukan bukti pembayaran.</p>
@if(in_array($row->status,['pending','confirmed']) && $row->starts_at > \Carbon\CarbonImmutable::now('Asia/Jakarta')->format('Y-m-d H:i:s'))
<details class="akd-closure-item"><summary>Batalkan booking</summary><form method="POST" action="{{ route('booking.transition',$row->id) }}" data-management-submit data-confirm="Batalkan seluruh slot booking #{{ $row->id }}?">@csrf<input type="hidden" name="action" value="cancel">
@include('components.dashboard.field',['name'=>'reason','label'=>'Alasan pembatalan seluruh booking','type'=>'textarea','required'=>true])
<button class="btn btn-outline-danger" type="submit">Batalkan seluruh booking</button><span data-management-status role="status"></span></form></details>@endif
@if($row->cancellation_reason)<p>Alasan pembatalan: {{ $row->cancellation_reason }}</p>@endif
@endif
</section><section class="akd-surface"><h2>Riwayat tindakan</h2><ul>@forelse($events as $event)<li>{{ $event->occurred_at }} WIB — {{ ['booking_requested'=>'Permintaan dicatat','booking_confirmed'=>'Booking dikonfirmasi','booking_recorded_confirmed'=>'Booking langsung dikonfirmasi','booking_expired'=>'Booking kedaluwarsa','booking_canceled'=>'Booking dibatalkan'][$event->action] ?? 'Perubahan booking' }}@if($event->reason): {{ $event->reason }}@endif</li>@empty<li>Belum ada riwayat tindakan pada alur baru.</li>@endforelse</ul></section></div>
@endsection

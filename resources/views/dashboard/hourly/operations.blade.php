@extends('layouts.admin')
@section('main-content')
<div class="akd-management">
<div class="akd-page-heading"><a href="{{ route('jadwal.index') }}">Kembali ke pilihan lapangan</a><h1>{{ $lapangan->nama_lapangan }}</h1><p>Atur jam main, tutup court, dan catat pemesanan langsung. Semua waktu menggunakan WIB.</p></div>
@include('components.dashboard.feedback', ['linkErrors'=>false])
@if(!$lapangan->hourly_booking_enabled)<p class="akd-note" role="status">Booking per jam belum aktif untuk venue ini. Pengaturan dapat disiapkan sebelum peralihan sistem oleh admin.</p>@endif
<div class="akd-operations-grid">
@if(auth()->user()->role === 'admin')
<section class="akd-surface"><h2>Jam operasional reguler</h2><p class="akd-muted">Berlaku setiap hari; pengecualian tanggal diatur terpisah.</p>
<form action="{{ route('jadwal.operations.save', $lapangan->id) }}" method="POST" data-management-submit>@csrf<input type="hidden" name="action" value="configure">
@include('components.dashboard.hour-select',['prefix'=>'regular','name'=>'open','label'=>'Buka','value'=>$lapangan->regular_opens_at])
@include('components.dashboard.hour-select',['prefix'=>'regular','name'=>'close','label'=>'Tutup','value'=>$lapangan->regular_closes_at])
@include('components.dashboard.field',['prefix'=>'regular-','name'=>'days','label'=>'Rentang pemesanan (hari, termasuk hari ini)','type'=>'number','value'=>$lapangan->booking_window_days,'required'=>true,'useOld'=>old('action')==='configure'])
@include('components.dashboard.field',['prefix'=>'regular-','name'=>'contact','label'=>'Kontak WhatsApp pengelola untuk pelanggan','type'=>'tel','value'=>$lapangan->booking_contact,'useOld'=>old('action')==='configure'])
@include('components.dashboard.field',['prefix'=>'regular-','name'=>'reason','label'=>'Alasan perubahan','required'=>true,'useOld'=>old('action')==='configure'])
<button class="btn btn-primary" type="submit">Simpan jam reguler</button><span role="status" data-management-status></span></form></section>
<section class="akd-surface"><h2>Court venue</h2><ul>@forelse($courts as $court)<li>{{ $court->name }} — {{ $court->is_active ? 'Terverifikasi' : 'Belum aktif' }}</li>@empty<li>Belum ada court yang diverifikasi.</li>@endforelse</ul>
<form action="{{ route('jadwal.operations.save', $lapangan->id) }}" method="POST" data-management-submit>@csrf<input type="hidden" name="action" value="court">
@include('components.dashboard.field',['prefix'=>'court-','name'=>'name','label'=>'Nama court fisik','required'=>true,'useOld'=>old('action')==='court'])
<label class="akd-check"><input type="checkbox" name="verified" value="1" required> Saya sudah memverifikasi court ini milik venue tersebut.</label>
<button class="btn btn-primary" type="submit">Tambahkan court terverifikasi</button><span role="status" data-management-status></span></form>
<p class="akd-muted">Kandidat court lama yang belum aktif menunggu rekonsiliasi data.</p></section>
<section class="akd-surface"><h2>Verifikasi kandidat court lama</h2>
@forelse($courts->where('is_active',false) as $candidate)
<form method="POST" action="{{ route('jadwal.operations.save',$lapangan->id) }}" class="akd-closure-item">@csrf<input type="hidden" name="action" value="court"><input type="hidden" name="existing_id" value="{{ $candidate->id }}"><input type="hidden" name="name" value="{{ $candidate->name }}"><p>{{ $candidate->name }}</p><label class="akd-check"><input type="checkbox" name="verified" value="1" required> Court ini sudah saya cocokkan dengan inventaris venue.</label><button class="btn btn-secondary" type="submit">Verifikasi {{ $candidate->name }}</button></form>
@empty<p>Tidak ada kandidat court yang menunggu verifikasi.</p>@endforelse</section>
@endif
</div>
<section class="akd-surface"><div class="akd-panel-heading"><div><h2>Jadwal harian</h2><p>Semua court dan slot ditampilkan, termasuk yang tidak tersedia.</p></div><a class="btn btn-secondary" href="{{ route('jadwal.operations', $lapangan->id) }}">Hari ini</a></div>
<form method="GET" action="{{ route('jadwal.operations', $lapangan->id) }}" class="akd-inline-filter"><label for="view-date">Tanggal main</label><input class="form-control" type="date" name="date" id="view-date" value="{{ $date }}" required><button class="btn btn-secondary" type="submit">Lihat tanggal</button></form>
<form action="{{ route('jadwal.operations.save', $lapangan->id) }}" method="POST" data-management-submit data-hourly-selection>@csrf<input type="hidden" name="action" value="reserve"><input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="key" value="{{ old('action') === 'reserve' ? old('key', $key) : $key }}">
<p class="akd-muted">Pilih jam berurutan pada satu court. Jam terpisah atau court berbeda dicatat sebagai booking terpisah.</p>
<div class="akd-court-grid">
@forelse($courts->where('is_active',true) as $court)
<fieldset class="akd-court-slots"><legend>{{ $court->name }}</legend>
@forelse(collect($slots)->where('court_id',$court->id) as $slot)
<label class="akd-slot-option {{ $slot['available'] ? '' : 'akd-slot-disabled' }}"><input type="checkbox" name="slot_ids[]" value="{{ $slot['id'] }}" data-slot-start="{{ $slot['starts_at'] }}" data-slot-end="{{ $slot['ends_at'] }}" data-court="{{ $court->id }}" @disabled(!$slot['available']) @checked($slot['available'] && old('action')==='reserve' && in_array($slot['id'],old('slot_ids',[])))><span><strong>{{ substr($slot['starts_at'],11,5) }}–{{ substr($slot['ends_at'],11,5) }}</strong><small>{{ $slot['label'] }}</small></span></label>
@empty<p class="akd-muted">Belum ada slot untuk tanggal ini.</p>@endforelse
</fieldset>
@empty<p>Belum ada court aktif.</p>@endforelse
</div>
<p role="status" data-slot-summary>Pilih slot untuk menentukan durasi booking.</p>
<div class="akd-operations-grid">
@include('components.dashboard.field',['name'=>'nama','label'=>'Nama pemesan','required'=>true,'useOld'=>old('action')==='reserve'])
@include('components.dashboard.field',['name'=>'phone','label'=>'Nomor WhatsApp/telepon','type'=>'tel','required'=>true,'useOld'=>old('action')==='reserve'])
@include('components.dashboard.field',['name'=>'source','label'=>'Sumber pemesanan','type'=>'select','options'=>['walk_in'=>'Datang langsung','whatsapp'=>'WhatsApp'],'required'=>true,'useOld'=>old('action')==='reserve'])
</div>
<label class="akd-check"><input type="checkbox" name="confirmed" value="1" @checked(old('action')==='reserve' && old('confirmed'))> Pemesanan sudah disepakati, langsung konfirmasi.</label>
<p class="akd-muted">Tanpa konfirmasi langsung, slot ditahan maksimal 60 menit atau sampai jam mulai. Dikonfirmasi bukan berarti sudah dibayar.</p>
<button class="btn btn-primary" type="submit">Catat booking</button><span role="status" data-management-status></span>
</form>
@if(auth()->user()->role==='admin')<form action="{{ route('jadwal.operations.save', $lapangan->id) }}" method="POST" class="mt-3">@csrf<input type="hidden" name="action" value="generate"><button class="btn btn-secondary" type="submit">Lengkapi slot dalam rentang pemesanan</button></form>@endif
</section>
<div class="akd-operations-grid">
<section class="akd-surface"><h2>Pengecualian tanggal</h2><p class="akd-muted">Untuk {{ $date }}. Mengganti jam reguler pada tanggal ini saja.</p>
@if($exception)<p>Pengaturan saat ini: {{ $exception->is_closed ? 'Libur' : substr($exception->opens_at,0,5).'–'.substr($exception->closes_at,0,5).' WIB' }}. {{ $exception->reason }}</p>@endif
<form action="{{ route('jadwal.operations.save',$lapangan->id) }}" method="POST" data-management-submit>@csrf<input type="hidden" name="action" value="exception"><input type="hidden" name="date" value="{{ $date }}">
<label class="akd-check"><input type="checkbox" name="closed" value="1"> Venue libur sehari penuh</label><p class="akd-muted">Untuk libur penuh, jam di bawah tidak digunakan.</p>
@include('components.dashboard.hour-select',['prefix'=>'exception','name'=>'open','label'=>'Jam buka khusus','value'=>$lapangan->regular_opens_at])
@include('components.dashboard.hour-select',['prefix'=>'exception','name'=>'close','label'=>'Jam tutup khusus','value'=>$lapangan->regular_closes_at])
@include('components.dashboard.field',['prefix'=>'exception-','name'=>'reason','label'=>'Alasan pengecualian','required'=>true,'useOld'=>old('action')==='exception'])
<button class="btn btn-primary" type="submit">Simpan pengecualian</button><span role="status" data-management-status></span></form></section>
<section class="akd-surface"><h2>Tutup slot court</h2><p class="akd-muted">Untuk {{ $date }}. Pilih seluruh jam operasional untuk menutup court seharian.</p>
<form action="{{ route('jadwal.operations.save',$lapangan->id) }}" method="POST" data-management-submit>@csrf<input type="hidden" name="action" value="close"><input type="hidden" name="date" value="{{ $date }}">
@include('components.dashboard.field',['prefix'=>'closure-','name'=>'court_id','label'=>'Court','type'=>'select','options'=>$courts->pluck('name','id'),'required'=>true,'useOld'=>old('action')==='close'])
@include('components.dashboard.hour-select',['prefix'=>'closure','name'=>'open','label'=>'Mulai penutupan','value'=>$lapangan->regular_opens_at])
@include('components.dashboard.hour-select',['prefix'=>'closure','name'=>'close','label'=>'Akhir penutupan','value'=>$lapangan->regular_closes_at])
@include('components.dashboard.field',['prefix'=>'closure-','name'=>'reason','label'=>'Alasan penutupan','required'=>true,'useOld'=>old('action')==='close'])
<button class="btn btn-secondary" type="submit">Tutup slot</button><span role="status" data-management-status></span></form></section>
</div>
<section class="akd-surface"><h2>Penutupan tanggal ini</h2>
@forelse($closures as $closure)<details class="akd-closure-item"><summary>{{ $courts->firstWhere('id',$closure->venue_court_id)->name }} · {{ substr($closure->starts_at,11,5) }}–{{ substr($closure->ends_at,11,5) }}</summary><p>{{ $closure->reason }}</p>
@if(auth()->user()->role==='admin' || auth()->id()===$closure->created_by)
<form method="POST" action="{{ route('jadwal.operations.save',$lapangan->id) }}">@csrf<input type="hidden" name="action" value="reopen"><input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="closure_id" value="{{ $closure->id }}">
@include('components.dashboard.field',['prefix'=>'reopen-'.$closure->id.'-','name'=>'reason','label'=>'Alasan membuka kembali','required'=>true,'useOld'=>false])
<button class="btn btn-secondary" type="submit">Buka kembali penutupan ini</button></form>@else<p>Penutupan ini hanya dapat dibuka oleh pembuatnya atau admin.</p>@endif</details>
@empty<p>Tidak ada penutupan court pada tanggal ini.</p>@endforelse
</section>
</div>
@endsection

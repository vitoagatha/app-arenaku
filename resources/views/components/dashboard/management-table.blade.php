<p class="akd-muted akd-table-hint">Gunakan pencarian untuk menemukan data. Geser tabel ke samping pada layar kecil.</p>
<div class="table-responsive" role="region" aria-label="{{ $caption }}" tabindex="0">
    <table class="table akd-table" id="dataTable">
        <caption class="sr-only">{{ $caption }}</caption>
        <thead><tr>@foreach($headers as $header)<th scope="col">{{ $header }}</th>@endforeach</tr></thead>
        <tbody>{{ $slot }}</tbody>
    </table>
</div>

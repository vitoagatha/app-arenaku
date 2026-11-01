@if($errors->any())
    <div class="akd-errors" role="alert" tabindex="-1" data-login-errors><h2>Periksa kembali isianmu</h2><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
@if(session('status'))<p class="akd-note" role="status">{{ session('status') }}</p>@endif

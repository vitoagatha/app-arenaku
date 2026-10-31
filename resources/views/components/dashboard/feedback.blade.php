@foreach (['success' => 'success', 'status' => 'success', 'error' => 'danger'] as $key => $type)
    @if(session($key))<div class="alert alert-{{ $type }}" role="{{ $key === 'error' ? 'alert' : 'status' }}">{{ session($key) }}</div>@endif
@endforeach
@if($errors->any())
    <div class="akd-errors" role="alert" tabindex="-1" data-error-summary><h2>Periksa kembali isianmu</h2><ul>@foreach($errors->messages() as $field => $messages)@foreach($messages as $message)<li>@if($linkErrors ?? true)<a href="#{{ $field }}">{{ $message }}</a>@else{{ $message }}@endif</li>@endforeach @endforeach</ul></div>
@endif

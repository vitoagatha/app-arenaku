<div class="form-group">
    <label for="{{ $name }}">{{ $label }}</label>
    <input class="form-control @error($name) is-invalid @enderror" type="{{ $type ?? 'text' }}" id="{{ $name }}" name="{{ $name }}" autocomplete="{{ $autocomplete ?? 'off' }}" @if(($type ?? 'text') === 'email') spellcheck="false" @endif @if(($type ?? 'text') !== 'password') value="{{ old($name, Auth::user()->getAttribute($name)) }}" @endif @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>
    @error($name)<p class="akd-field-error" id="{{ $name }}-error">{{ $message }}</p>@enderror
</div>

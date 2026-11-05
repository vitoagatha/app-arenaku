@php
    $hourAction = ['regular'=>'configure','exception'=>'exception','closure'=>'close'][$prefix] ?? '';
    $hourValue = old('action') === $hourAction ? old($name, $value ?? '') : ($value ?? '');
@endphp
<div class="form-group"><label for="{{ $prefix }}-{{ $name }}">{{ $label }} (WIB)</label><select class="form-control" id="{{ $prefix }}-{{ $name }}" name="{{ $name }}" required @if(old('action') === $hourAction && $errors->has($name)) aria-invalid="true" aria-describedby="{{ $prefix }}-{{ $name }}-error" @endif>
<option value="">Pilih jam</option>
@for($hour=0; $hour<24; $hour++) @php($time=sprintf('%02d:00', $hour))
<option value="{{ $time }}" @selected($time === substr((string)$hourValue,0,5))>{{ $time }}</option>
@endfor
</select>
@if(old('action') === $hourAction)
    @error($name)
        <p class="akd-field-error" id="{{ $prefix }}-{{ $name }}-error">{{ $message }}</p>
    @enderror
@endif
</div>

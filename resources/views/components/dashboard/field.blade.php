@php
    $fieldId = ($prefix ?? '') . $name;
    $fieldValue = ($useOld ?? true) ? old($name, $value ?? '') : ($value ?? '');
@endphp
<div class="form-group">
    <label for="{{ $fieldId }}">{{ $label }}{{ ($required ?? false) ? ' (wajib)' : '' }}</label>
    @if (($type ?? 'text') === 'textarea')
        <textarea class="form-control" id="{{ $fieldId }}" name="{{ $name }}" rows="4" @if($required ?? false) required @endif @error($name) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @enderror>{{ $fieldValue }}</textarea>
    @elseif (($type ?? 'text') === 'select')
        <select class="form-control" id="{{ $fieldId }}" name="{{ $name }}" @if($required ?? false) required @endif @error($name) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @enderror>
            @foreach($options as $optionValue => $optionLabel)<option value="{{ $optionValue }}" {{ (string)$fieldValue === (string)$optionValue ? 'selected' : '' }}>{{ $optionLabel }}</option>@endforeach
        </select>
    @else
        <input class="form-control" type="{{ $type ?? 'text' }}" id="{{ $fieldId }}" name="{{ $name }}" @if(($type ?? 'text') !== 'password') value="{{ $fieldValue }}" @endif autocomplete="{{ $autocomplete ?? 'off' }}" @if(($type ?? 'text') === 'email') spellcheck="false" @endif @if($required ?? false) required @endif @error($name) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @enderror>
    @endif
    @error($name)<p class="akd-field-error" id="{{ $fieldId }}-error">{{ $message }}</p>@enderror
</div>

<div class="form-group">
    <label for="image">Foto lapangan</label>
    <p id="image-help" class="akd-muted">JPEG, PNG, JPG, GIF, atau SVG. Maksimal 2 MB. @if(isset($lapangan))Kosongkan untuk mempertahankan foto saat ini.@endif</p>
    <input type="file" class="form-control-file" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/svg+xml" data-image-upload aria-describedby="image-help @error('image') image-error @enderror" @error('image') aria-invalid="true" @enderror>
    @error('image')<p class="akd-field-error" id="image-error">{{ $message }}</p>@enderror
    @if(isset($lapangan))@include('components.dashboard.venue-image')@endif
    <img class="akd-upload-preview" alt="Pratinjau foto baru" width="240" height="160" data-upload-preview hidden>
    <p role="status" data-upload-status></p>
</div>

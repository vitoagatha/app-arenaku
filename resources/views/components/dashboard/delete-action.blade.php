<form action="{{ $action }}" method="POST" class="akd-delete-form" data-confirm="Hapus {{ $label }}? Tindakan ini akan menghapus data tersebut." onsubmit="return window.confirm(this.dataset.confirm)">
    @csrf
    @method('DELETE')
    <button class="btn btn-outline-danger btn-sm" type="submit" aria-label="Hapus {{ $label }}">Hapus</button>
</form>

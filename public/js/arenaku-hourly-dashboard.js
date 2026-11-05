document.addEventListener('submit', (event) => {
    const form = event.target;
    if (form.matches('[data-confirm]:not([onsubmit])') && !window.confirm(form.dataset.confirm)) event.preventDefault();
}, true);

const selection = document.querySelector('[data-hourly-selection]');
if (selection) {
    const summary = selection.querySelector('[data-slot-summary]');
    summary.tabIndex = -1;
    const selected = () => [...selection.querySelectorAll('[name="slot_ids[]"]:checked')].sort((a, b) => a.dataset.slotStart.localeCompare(b.dataset.slotStart));
    const valid = (items) => items.length && items.every((item, index) => !item.disabled && item.dataset.court === items[0].dataset.court && (!index || items[index - 1].dataset.slotEnd === item.dataset.slotStart));
    const update = () => {
        const items = selected();
        summary.textContent = !items.length ? 'Pilih slot untuk menentukan durasi booking.' : valid(items)
            ? items[0].dataset.slotStart.slice(0, 16) + ' sampai ' + items[items.length - 1].dataset.slotEnd.slice(11, 16) + ' WIB, ' + items.length + ' jam.'
            : 'Periksa pilihan: gunakan slot tersedia yang berurutan pada satu court.';
    };
    selection.addEventListener('change', update);
    selection.addEventListener('submit', (event) => {
        if (!valid(selected())) { event.preventDefault(); update(); summary.focus(); }
    }, true);
    const poll = async () => {
        if (document.hidden || selection.dataset.submitting) return;
        try {
            const response = await fetch(window.location.href, {headers: {Accept: 'application/json'}, cache: 'no-store'});
            if (!response.ok) throw new Error('unavailable');
            const data = await response.json();
            const slots = new Map(data.slots.map(slot => [String(slot.id), slot]));
            selection.querySelectorAll('[name="slot_ids[]"]').forEach(input => {
                const slot = slots.get(input.value);
                input.disabled = !slot || !slot.available;
                const label = input.closest('label');
                label.classList.toggle('akd-slot-disabled', input.disabled);
                label.querySelector('small').textContent = slot ? slot.label : 'Tidak tersedia';
            });
            update();
        } catch (_) {
            summary.textContent = 'Ketersediaan belum berhasil diperbarui. Periksa koneksi dan coba kembali sebelum mencatat booking.';
        }
    };
    update();
    setInterval(poll, 30000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
}

document.querySelectorAll('form').forEach(form => {
    const holiday = form.querySelector('[name="closed"]');
    if (!holiday) return;
    const update = () => form.querySelectorAll('[name="open"],[name="close"]').forEach(input => { input.disabled = holiday.checked; input.required = !holiday.checked; });
    holiday.addEventListener('change', update);
    update();
});

const pending = document.querySelector('[data-pending-refresh]');
if (pending) {
    let signature;
    const poll = async () => {
        if (document.hidden) return;
        try {
            const response = await fetch(window.location.href, {headers: {Accept: 'application/json'}, cache: 'no-store'});
            if (!response.ok) throw new Error('unavailable');
            const data = await response.json();
            const next = JSON.stringify(data);
            if (signature && signature !== next) pending.textContent = 'Ada perubahan permintaan booking. Gunakan Perbarui daftar untuk melihat kondisi terbaru.';
            signature = next;
        } catch (_) { pending.textContent = 'Pembaruan otomatis belum berhasil. Gunakan Perbarui daftar.'; }
    };
    poll();
    setInterval(poll, 30000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
}

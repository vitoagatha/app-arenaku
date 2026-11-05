const form = document.querySelector('[data-public-hourly]');
if (form) {
    const controls = [...form.querySelectorAll('input[name="slot_ids[]"]')];
    const summary = form.querySelector('[data-selection-summary]');
    const feedback = form.querySelector('[data-availability-feedback]');
    const deadline = new Intl.DateTimeFormat('id-ID', {
        timeZone: 'Asia/Jakarta', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit',
    });
    let clock = Date.parse(form.dataset.now);
    let clockReceived = performance.now();
    let refreshing = false;
    const selected = () => controls.filter(input => input.checked).sort((a, b) => a.dataset.start.localeCompare(b.dataset.start));
    const startTime = input => Date.parse(input.dataset.start.replace(' ', 'T') + '+07:00');

    function valid(items) {
        return items.length > 0 && items.every((item, index) => item.dataset.available === '1'
            && item.dataset.court === items[0].dataset.court
            && (!index || items[index - 1].dataset.end === item.dataset.start));
    }

    function update() {
        const now = clock + performance.now() - clockReceived;
        for (const input of controls) {
            if (startTime(input) <= now) {
                input.dataset.available = '0';
                input.closest('label').querySelector('[data-slot-label]').textContent = 'Tidak tersedia';
            }
            const unavailable = input.dataset.available !== '1';
            // Keep a stale selection in the submitted set until the customer removes it.
            input.disabled = unavailable && !input.checked;
            input.closest('label').classList.toggle('ak-slot-unavailable', unavailable);
            input.closest('label').classList.toggle('ak-slot-ready', !unavailable);
        }
        const items = selected();
        let message = 'Pilih slot untuk melihat ringkasan waktu main.';
        if (items.length) {
            message = valid(items)
                ? items[0].dataset.courtName + ' · ' + items[0].dataset.start.slice(11, 16)
                    + '–' + items.at(-1).dataset.end.slice(11, 16) + ' WIB · ' + items.length + ' jam. Perkiraan batas konfirmasi '
                    + deadline.format(Math.min(now + 3600000, startTime(items[0]))) + ' WIB.'
                : 'Periksa pilihanmu. Gunakan slot yang masih tersedia dan berurutan pada satu court.';
        }
        if (summary.textContent !== message) summary.textContent = message;
    }

    async function refresh() {
        if (document.hidden || refreshing || form.dataset.submitting) return;
        refreshing = true;
        try {
            const response = await fetch(form.dataset.availability, { headers: { Accept: 'application/json' }, cache: 'no-store' });
            if (!response.ok) throw new Error('Availability request failed');
            const result = await response.json();
            clock = Date.parse(result.now);
            clockReceived = performance.now();
            const slots = new Map(result.slots.map(slot => [String(slot.id), slot]));
            for (const input of controls) {
                const slot = slots.get(input.value);
                input.dataset.available = slot?.available ? '1' : '0';
                input.closest('label').querySelector('[data-slot-label]').textContent = slot?.label ?? 'Tidak tersedia';
            }
            const visibleIds = new Set(controls.map(input => input.value));
            feedback.textContent = [...slots.keys()].some(id => !visibleIds.has(id))
                ? 'Ada slot baru. Pilih Lihat jadwal untuk menampilkan perubahan.' : '';
            update();
        } catch (_) {
            feedback.textContent = 'Jadwal belum berhasil diperbarui. Ketersediaan akan diperiksa lagi saat kamu mengirim permintaan.';
        } finally {
            refreshing = false;
        }
    }

    form.addEventListener('change', update);
    form.addEventListener('submit', event => {
        update();
        if (!valid(selected())) {
            event.preventDefault();
            event.stopImmediatePropagation();
            summary.focus();
        }
    }, true);
    setInterval(update, 1000);
    setInterval(refresh, 30000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) { update(); refresh(); } });
    window.addEventListener('pageshow', event => { if (event.persisted) refresh(); });
    update();
}

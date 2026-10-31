(() => {
    const body = document.body;
    const sidebar = document.getElementById('dashboard-navigation');
    const toggle = document.querySelector('[data-nav-toggle]');
    const backdrop = document.querySelector('.akd-backdrop');
    const workspace = document.querySelector('.akd-workspace');
    const mobile = window.matchMedia('(max-width: 767px)');
    body.classList.add('akd-enhanced');

    function setOpen(open, restoreFocus = true) {
        open = open && mobile.matches;
        body.classList.toggle('akd-nav-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        backdrop.hidden = !open;
        workspace.inert = open;
        if (open) sidebar.querySelector('[data-nav-close]').focus();
        else if (restoreFocus && mobile.matches) toggle.focus();
    }
    toggle.addEventListener('click', () => setOpen(!body.classList.contains('akd-nav-open')));
    document.querySelectorAll('[data-nav-close]').forEach(button => button.addEventListener('click', () => setOpen(false)));
    mobile.addEventListener('change', () => setOpen(false, false));
    document.addEventListener('keydown', event => {
        if (!body.classList.contains('akd-nav-open')) return;
        if (event.key === 'Escape') { event.preventDefault(); setOpen(false); }
        if (event.key !== 'Tab') return;
        const controls = [...sidebar.querySelectorAll('a[href], button')];
        const first = controls[0];
        const last = controls[controls.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });
    const errorSummary = document.querySelector('[data-error-summary]');
    if (errorSummary) errorSummary.focus();
    const profile = document.querySelector('[data-profile-form]');
    if (profile) {
        const button = profile.querySelector('[type="submit"]');
        const status = profile.querySelector('[data-profile-status]');
        const reset = () => { button.disabled = false; button.textContent = 'Simpan perubahan'; profile.removeAttribute('aria-busy'); status.textContent = ''; };
        profile.addEventListener('submit', event => {
            if (button.disabled) { event.preventDefault(); return; }
            button.disabled = true;
            button.textContent = 'Menyimpan…';
            profile.setAttribute('aria-busy', 'true');
            status.textContent = 'Sedang menyimpan perubahan profil.';
        });
        window.addEventListener('pageshow', reset);
    }
})();

// Management tables redraw rows; delegate image recovery and deletion feedback.
document.addEventListener('error', event => {
    const image = event.target;
    if (image.matches && image.matches('[data-management-image]')) {
        image.hidden = true;
        image.nextElementSibling.hidden = false;
    }
}, true);
document.querySelectorAll('[data-management-image]').forEach(image => {
    if (image.complete && image.naturalWidth === 0) { image.hidden = true; image.nextElementSibling.hidden = false; }
});

document.querySelectorAll('[data-image-upload]').forEach(input => {
    const group = input.closest('.form-group');
    const preview = group.querySelector('[data-upload-preview]');
    const status = group.querySelector('[data-upload-status]');
    let objectUrl;
    input.addEventListener('change', () => {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        preview.hidden = true;
        status.textContent = '';
        const file = input.files[0];
        if (!file) return;
        objectUrl = URL.createObjectURL(file);
        preview.onload = () => { preview.hidden = false; status.textContent = 'Pratinjau foto baru: ' + file.name; };
        preview.onerror = () => { status.textContent = 'Pratinjau tidak dapat ditampilkan. Foto akan diperiksa saat disimpan.'; };
        preview.src = objectUrl;
    });
});

const schedulePage = document.querySelector('[data-schedule-page]');
const scheduleStorageKey = 'arenaku-schedule-form:' + window.location.pathname;
document.addEventListener('submit', event => {
    const form = event.target;
    if (event.defaultPrevented || !form.matches('[data-management-submit], .akd-delete-form')) return;
    if (form.dataset.submitting) { event.preventDefault(); return; }
    if (form.matches('[data-schedule-form]')) {
        try { sessionStorage.setItem(scheduleStorageKey, form.closest('.modal').id); } catch (_) { /* Optional recovery when storage is available. */ }
    }
    const button = form.querySelector('[type="submit"]');
    button.dataset.originalLabel = button.textContent;
    button.disabled = true;
    button.textContent = 'Memproses…';
    form.dataset.submitting = 'true';
    form.setAttribute('aria-busy', 'true');
    const status = form.querySelector('[data-management-status]');
    if (status) status.textContent = 'Sedang menyimpan data.';
});
window.addEventListener('pageshow', () => {
    document.querySelectorAll('[data-submitting]').forEach(form => {
        const button = form.querySelector('[type="submit"]');
        button.disabled = false;
        button.textContent = button.dataset.originalLabel;
        delete form.dataset.submitting;
        form.removeAttribute('aria-busy');
        const status = form.querySelector('[data-management-status]');
        if (status) status.textContent = '';
    });
});
if (schedulePage) {
    let modalId;
    try { modalId = sessionStorage.getItem(scheduleStorageKey); sessionStorage.removeItem(scheduleStorageKey); } catch (_) { /* Leave the visible server error summary available. */ }
    const modal = modalId ? document.getElementById(modalId) : null;
    if (schedulePage.dataset.hasErrors === 'true' && modal) {
        const oldValues = JSON.parse(schedulePage.dataset.oldValues || '{}');
        modal.querySelectorAll('input:not([type="hidden"]), select').forEach(field => {
            if (Object.prototype.hasOwnProperty.call(oldValues, field.name)) field.value = oldValues[field.name];
        });
        window.jQuery(modal).one('shown.bs.modal', () => {
            const field = modal.querySelector('[aria-invalid="true"]');
            if (field) field.focus();
        }).modal('show');
    }
}

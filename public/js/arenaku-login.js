(() => {
    const form = document.querySelector('[data-login-form]');
    if (!form) return;
    const submit = form.querySelector('[type="submit"]');
    const status = form.querySelector('[data-login-status]');
    const initialLabel = submit.textContent;
    const toggles = [...form.querySelectorAll('[data-password-toggle]')];
    toggles.forEach(toggle => {
        const password = document.getElementById(toggle.getAttribute('aria-controls'));
        if (!password) return;
        const label = document.querySelector('label[for="' + password.id + '"]');
        const fieldLabel = label ? label.textContent.toLowerCase() : 'kata sandi';
        toggle.setAttribute('aria-label', 'Tampilkan ' + fieldLabel);
        toggle.hidden = false;
        toggle.addEventListener('click', () => {
            const reveal = password.type === 'password';
            password.type = reveal ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', String(reveal));
            toggle.textContent = reveal ? 'Sembunyikan' : 'Tampilkan';
            toggle.setAttribute('aria-label', toggle.textContent + ' ' + fieldLabel);
        });
    });
    form.addEventListener('submit', event => {
        if (submit.disabled) { event.preventDefault(); return; }
        submit.disabled = true;
        submit.textContent = form.dataset.loadingLabel || 'Memeriksa akun…';
        form.setAttribute('aria-busy', 'true');
        status.textContent = form.dataset.loadingStatus || 'Sedang memeriksa akun. Tunggu sebentar.';
    });
    window.addEventListener('pageshow', () => {
        submit.disabled = false;
        submit.textContent = initialLabel;
        form.removeAttribute('aria-busy');
        status.textContent = '';
        toggles.forEach(toggle => {
            const password = document.getElementById(toggle.getAttribute('aria-controls'));
            if (!password) return;
            password.type = 'password';
            toggle.setAttribute('aria-pressed', 'false');
            toggle.textContent = 'Tampilkan';
            const label = document.querySelector('label[for="' + password.id + '"]');
            toggle.setAttribute('aria-label', 'Tampilkan ' + (label ? label.textContent.toLowerCase() : 'kata sandi'));
        });
    });
    const errors = document.querySelector('[data-login-errors]');
    if (errors) errors.focus();
})();

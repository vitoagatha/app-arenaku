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
        toggle.hidden = false;
        toggle.addEventListener('click', () => {
            const reveal = password.type === 'password';
            password.type = reveal ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', String(reveal));
            toggle.textContent = reveal ? 'Sembunyikan' : 'Tampilkan';
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
        });
    });
    const errors = document.querySelector('[data-login-errors]');
    if (errors) errors.focus();
})();

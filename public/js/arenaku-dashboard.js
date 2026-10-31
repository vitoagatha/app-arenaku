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

document.querySelectorAll('[data-venue-image]').forEach((image) => {
    const showFallback = () => {
        image.hidden = true;
        image.nextElementSibling.hidden = false;
    };
    image.addEventListener('error', showFallback);
    if (image.complete && image.naturalWidth === 0) showFallback();
});
// Preserve result scrolling after the existing POST search, without animation.
window.addEventListener('load', () => {
    const errors = document.querySelector('[data-error-summary]');
    if (errors) {
        errors.focus();
        return;
    }
    const result = document.querySelector('[data-scroll-result]');
    if (result) result.scrollIntoView({ behavior: 'instant', block: 'start' });
});

document.querySelectorAll('[data-submit-feedback]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (form.dataset.submitting === 'true') {
            event.preventDefault();
            return;
        }
        form.dataset.submitting = 'true';
        form.setAttribute('aria-busy', 'true');
        const button = form.querySelector('button[type="submit"]');
        button.dataset.originalLabel = button.textContent;
        button.textContent = button.dataset.loadingLabel;
        button.disabled = true;
        form.querySelector('.ak-submit-status').textContent = 'Sedang memproses. Tunggu hasil pengiriman.';
    });
});

// Back/forward cache can restore a form with its submitting state intact.
window.addEventListener('pageshow', () => {
    document.querySelectorAll('[data-submit-feedback]').forEach((form) => {
        delete form.dataset.submitting;
        form.removeAttribute('aria-busy');
        const button = form.querySelector('button[type="submit"]');
        button.disabled = false;
        if (button.dataset.originalLabel) button.textContent = button.dataset.originalLabel;
        form.querySelector('.ak-submit-status').textContent = '';
    });
});

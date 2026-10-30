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
    const result = document.querySelector('[data-scroll-result]');
    if (result) result.scrollIntoView({ behavior: 'instant', block: 'start' });
});

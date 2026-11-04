// Elemen .reveal muncul sekali saat masuk layar; navbar menebal setelah digulir.
export function initReveal(root, { reduce }) {
    const items = root.querySelectorAll('.reveal');

    if (reduce || !('IntersectionObserver' in window)) {
        items.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });

    items.forEach((el) => observer.observe(el));
}

// Jeda animasi saat bagian berada di luar layar.
export function initOffscreenPause(root) {
    if (!('IntersectionObserver' in window)) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => entry.target.classList.toggle('is-offscreen', !entry.isIntersecting));
    });

    root.querySelectorAll('[data-pause-offscreen]').forEach((el) => observer.observe(el));
}

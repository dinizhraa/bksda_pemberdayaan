// Angka berhitung naik dari 0 saat terlihat. Nilai akhir sudah ada di HTML (aman tanpa JS).
const format = (el, value) => Number(value).toLocaleString('id-ID', {
    minimumFractionDigits: Number(el.dataset.decimals || 0),
    maximumFractionDigits: Number(el.dataset.decimals || 0),
});

export function initCounters(root, { reduce }) {
    const els = root.querySelectorAll('[data-count]');
    if (reduce || !('IntersectionObserver' in window)) return;

    els.forEach((el) => { el.textContent = format(el, 0); });

    const run = (el) => {
        const target = Number(el.dataset.count);
        const start = performance.now();
        const duration = 1800;

        const tick = (now) => {
            const t = Math.min((now - start) / duration, 1);
            const eased = 1 - (1 - t) ** 3;
            el.textContent = format(el, target * eased);
            if (t < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            run(entry.target);
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.5 });

    els.forEach((el) => observer.observe(el));
}

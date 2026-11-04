// Progres gulir halaman, navbar menebal, dan garis waktu yang terisi saat digulir.
export function initScroll(root) {
    const nav = root.querySelector('[data-nav]');
    const timelines = [...root.querySelectorAll('[data-timeline]')];
    let ticking = false;

    const update = () => {
        ticking = false;
        const max = document.documentElement.scrollHeight - window.innerHeight;
        root.style.setProperty('--sp', max > 0 ? (window.scrollY / max).toFixed(4) : '0');
        nav?.classList.toggle('is-scrolled', window.scrollY > 12);

        timelines.forEach((timeline) => {
            const line = window.innerHeight * 0.6;
            const box = timeline.getBoundingClientRect();
            const progress = Math.min(Math.max((line - box.top) / box.height, 0), 1);
            timeline.style.setProperty('--p', progress.toFixed(3));

            timeline.querySelectorAll('[data-step]').forEach((step) => {
                const rect = step.getBoundingClientRect();
                step.classList.toggle('is-active', rect.top + rect.height / 2 < line);
            });
        });
    };

    const schedule = () => {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(update);
    };

    window.addEventListener('scroll', schedule, { passive: true });
    window.addEventListener('resize', schedule);
    update();
}

// Menu navigasi menyorot seksi yang sedang terlihat.
export function initSpy(root) {
    if (!('IntersectionObserver' in window)) return;

    const links = [...root.querySelectorAll('[data-spy]')];
    const targets = [...new Set(links.map((link) => link.getAttribute('href')))]
        .map((hash) => document.querySelector(hash))
        .filter(Boolean);

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            links.forEach((link) => {
                link.classList.toggle('nav-pill-active', link.getAttribute('href') === `#${entry.target.id}`);
            });
        });
    }, { rootMargin: '-40% 0px -55% 0px' });

    targets.forEach((section) => observer.observe(section));
}

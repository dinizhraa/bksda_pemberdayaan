// Interaksi kursor: sorotan & kemiringan kartu, parallax hero, tombol magnetik, cahaya kursor.
export function initPointer(root, { reduce, fine }) {
    if (!fine || reduce) return;

    // 1. Sorotan cahaya + kemiringan 3D pada kartu
    root.querySelectorAll('.spotlight, [data-tilt-card]').forEach((card) => {
        const tilts = card.hasAttribute('data-tilt-card');
        let frame;

        card.addEventListener('pointermove', (event) => {
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(() => {
                const box = card.getBoundingClientRect();
                const x = event.clientX - box.left;
                const y = event.clientY - box.top;
                card.style.setProperty('--mx', `${x}px`);
                card.style.setProperty('--my', `${y}px`);

                if (tilts) {
                    card.style.setProperty('--ry', `${((x / box.width) - 0.5) * 9}deg`);
                    card.style.setProperty('--rx', `${-((y / box.height) - 0.5) * 7}deg`);
                }
            });
        });

        if (tilts) {
            card.addEventListener('pointerleave', () => {
                card.style.removeProperty('--rx');
                card.style.removeProperty('--ry');
            });
        }
    });

    // 2. Parallax & kemiringan ilustrasi hero
    const area = root.querySelector('[data-tilt-area]');
    if (area) {
        let frame;
        area.addEventListener('pointermove', (event) => {
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(() => {
                const box = area.getBoundingClientRect();
                area.style.setProperty('--px', ((event.clientX - box.left) / box.width - 0.5).toFixed(3));
                area.style.setProperty('--py', ((event.clientY - box.top) / box.height - 0.5).toFixed(3));
            });
        });
        area.addEventListener('pointerleave', () => {
            area.style.removeProperty('--px');
            area.style.removeProperty('--py');
        });
    }

    // 3. Tombol magnetik (memakai properti `translate` agar tidak bentrok dengan hover)
    const magnets = [...root.querySelectorAll('[data-magnetic]')];
    // 4. Cahaya yang menyusul kursor
    const glow = root.querySelector('[data-cursor-glow]');
    let target = { x: 0, y: 0 };
    let current = { x: 0, y: 0 };
    let running = false;

    const loop = () => {
        current.x += (target.x - current.x) * 0.12;
        current.y += (target.y - current.y) * 0.12;
        glow.style.transform = `translate3d(${current.x}px, ${current.y}px, 0)`;
        if (Math.abs(target.x - current.x) + Math.abs(target.y - current.y) > 0.5) {
            requestAnimationFrame(loop);
        } else {
            running = false;
        }
    };

    let frame;
    window.addEventListener('pointermove', (event) => {
        cancelAnimationFrame(frame);
        frame = requestAnimationFrame(() => {
            magnets.forEach((el) => {
                const box = el.getBoundingClientRect();
                const dx = event.clientX - (box.left + box.width / 2);
                const dy = event.clientY - (box.top + box.height / 2);
                const near = Math.hypot(dx, dy) < Math.max(box.width, 110);
                el.style.translate = near ? `${(dx * 0.22).toFixed(1)}px ${(dy * 0.32).toFixed(1)}px` : '';
            });
        });

        if (!glow) return;
        target = { x: event.clientX, y: event.clientY };
        glow.classList.add('is-active');
        if (!running) {
            running = true;
            requestAnimationFrame(loop);
        }
    }, { passive: true });

    document.addEventListener('pointerleave', () => glow?.classList.remove('is-active'));
}

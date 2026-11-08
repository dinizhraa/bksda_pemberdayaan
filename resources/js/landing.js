// Titik masuk interaksi landing page. Tiap efek ada di resources/js/landing-fx/.
import { initCounters } from './landing-fx/counter';
import { initPointer } from './landing-fx/pointer';
import { initOffscreenPause, initReveal } from './landing-fx/reveal';
import { initScroll } from './landing-fx/scroll';
import { initSpy } from './landing-fx/spy';

window.__landingReady = true;

const root = document.getElementById('landing');
const env = {
    reduce: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
    fine: window.matchMedia('(hover: hover) and (pointer: fine)').matches,
};

// Satu efek yang gagal tidak boleh merusak efek lain.
const run = (init) => {
    try {
        init(root, env);
    } catch (error) {
        console.error(error);
    }
};

try {
    initReveal(root, env);
} catch (error) {
    // Bila reveal gagal, tampilkan semua konten apa adanya.
    document.documentElement.classList.remove('js');
    console.error(error);
}

[initOffscreenPause, initSpy, initCounters, initScroll, initPointer].forEach(run);

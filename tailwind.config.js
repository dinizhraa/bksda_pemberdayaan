import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans Variable"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Teal utama pada desain referensi
                brand: {
                    50: '#effaf8',
                    100: '#d4f3ee',
                    200: '#a9e6dd',
                    300: '#74d2c6',
                    400: '#3fb8aa',
                    500: '#1d9e92',
                    600: '#138177',
                    700: '#0f675f',
                    800: '#0e524c',
                    900: '#0c423e',
                },
                // Warna teks navy-kebiruan
                ink: {
                    900: '#0f2437',
                    700: '#334a5e',
                    500: '#6a7d8f',
                    400: '#97a8b7',
                },
            },
            borderRadius: {
                '4xl': '2rem',
            },
            boxShadow: {
                glass: '0 10px 40px -12px rgb(31 94 110 / 0.20)',
            },
            // Animasi landing page: hanya transform/opacity agar tetap ringan (GPU)
            keyframes: {
                float: { '0%, 100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-12px)' } },
                'float-sm': { '0%, 100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-5px)' } },
                drift: {
                    '0%, 100%': { transform: 'translate3d(0, 0, 0) scale(1)' },
                    '50%': { transform: 'translate3d(4%, 6%, 0) scale(1.1)' },
                },
                rise: { from: { opacity: '0', transform: 'translateY(22px)' }, to: { opacity: '1', transform: 'translateY(0)' } },
                'rise-zoom': {
                    from: { opacity: '0', transform: 'translateY(24px) scale(0.94)' },
                    to: { opacity: '1', transform: 'translateY(0) scale(1)' },
                },
                draw: { to: { strokeDashoffset: '0' } },
                grow: { from: { transform: 'scaleY(0)' }, to: { transform: 'scaleY(1)' } },
                shine: {
                    from: { transform: 'translateX(-120%) skewX(-12deg)' },
                    to: { transform: 'translateX(420%) skewX(-12deg)' },
                },
                'fade-in': { from: { opacity: '0' }, to: { opacity: '1' } },
                'word-in': {
                    from: { opacity: '0', transform: 'translateY(70%) rotate(5deg)', filter: 'blur(10px)' },
                    to: { opacity: '1', transform: 'translateY(0) rotate(0)', filter: 'blur(0)' },
                },
                'rotate-word': {
                    '0%': { opacity: '0', transform: 'translateY(60%)' },
                    '4%, 22%': { opacity: '1', transform: 'translateY(0)' },
                    '26%, 100%': { opacity: '0', transform: 'translateY(-60%)' },
                },
                marquee: { from: { transform: 'translateX(0)' }, to: { transform: 'translateX(-50%)' } },
                particle: {
                    '0%': { opacity: '0', transform: 'translate3d(0, 0, 0) scale(0.6)' },
                    '12%': { opacity: '0.8' },
                    '100%': { opacity: '0', transform: 'translate3d(var(--dx, 24px), calc(var(--rise, 700px) * -1), 0) scale(1)' },
                },
                glow: { from: { opacity: '0.5', transform: 'scale(1)' }, to: { opacity: '0.9', transform: 'scale(1.15)' } },
                typing: { from: { width: '0' }, to: { width: 'var(--w, 24ch)' } },
                caret: { '50%': { borderColor: 'transparent' } },
            },
            animation: {
                float: 'float 6s ease-in-out infinite',
                'float-sm': 'float-sm 8s ease-in-out infinite',
                drift: 'drift 18s ease-in-out infinite',
                rise: 'rise 0.8s cubic-bezier(0.2, 0.7, 0.2, 1) both',
                reveal: 'rise 0.8s cubic-bezier(0.2, 0.7, 0.2, 1) backwards',
                'reveal-zoom': 'rise-zoom 0.8s cubic-bezier(0.2, 0.7, 0.2, 1) backwards',
                draw: 'draw 1.8s ease-out 0.6s forwards',
                grow: 'grow 0.9s cubic-bezier(0.2, 0.7, 0.2, 1) both',
                shine: 'shine 0.9s ease-out',
                'fade-in': 'fade-in 0.4s ease-out both',
                'word-in': 'word-in 0.9s cubic-bezier(0.2, 0.7, 0.2, 1) both',
                'rotate-word': 'rotate-word 12s ease-in-out infinite',
                marquee: 'marquee 38s linear infinite',
                particle: 'particle 20s linear infinite',
                glow: 'glow 5s ease-in-out infinite alternate',
                typing: 'typing 4s steps(26, end) infinite alternate, caret 0.8s step-end infinite',
                'spin-slow': 'spin 60s linear infinite',
                'spin-rev': 'spin 90s linear infinite reverse',
            },
        },
    },

    plugins: [forms],
};

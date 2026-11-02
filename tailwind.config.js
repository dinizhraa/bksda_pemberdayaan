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
        },
    },

    plugins: [forms],
};

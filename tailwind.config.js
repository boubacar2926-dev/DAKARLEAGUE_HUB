import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                display: ['Poppins', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                background: '#0D0D0D',
                surface: '#1A1A1A',
                primary: {
                    DEFAULT: '#B6FF3B',
                    dark: '#7ACC00',
                },
                'text-muted': '#B3B3B3',
                border: '#2A2A2A',
                success: '#B6FF3B',
                danger: '#FF4D4D',
            },
        },
    },

    plugins: [forms],
};

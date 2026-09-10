import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Remap brand accent to a rich, high-contrast Golden & Yellow palette
                indigo: {
                    50: '#fffbeb',   // soft warm ivory / pale pearl gold
                    100: '#fef3c7',  // light champagne gold
                    200: '#fde68a',  // sunlight gold
                    300: '#fcd34d',  // bright canary gold
                    400: '#fbbf24',  // radiant pure gold
                    500: '#eab308',  // rich metallic gold
                    600: '#b45309',  // burnished gold / amber-gold (high contrast on white)
                    700: '#92400e',  // antique deep bronze gold
                    800: '#78350f',  // roasted dark bronze gold
                    900: '#451a03',  // deepest luxury dark gold
                    950: '#1c1917',  // warm obsidian charcoal for sharp black-contrast
                },
                gold: {
                    50: '#fffbeb',
                    100: '#fef3c7',
                    200: '#fde68a',
                    300: '#fcd34d',
                    400: '#fbbf24',
                    500: '#f59e0b',
                    600: '#d97706',
                    700: '#b45309',
                    800: '#92400e',
                    900: '#78350f',
                    950: '#451a03',
                },
            },
        },
    },

    plugins: [forms],
};

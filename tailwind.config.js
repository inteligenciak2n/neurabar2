import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                heading: ['Poppins', ...defaultTheme.fontFamily.sans],
                body:    ['Manrope', ...defaultTheme.fontFamily.sans],
                sans:    ['Manrope', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Paleta NeuraBar (rebranding 2026-08): Azul Intenso + Dourado Glow
                primary:     { DEFAULT: '#293b4f', foreground: '#ffffff', hover: '#1e2a3a', light: '#dde3ea' },
                accent:      { DEFAULT: '#a28665', foreground: '#ffffff' },
                destructive: { DEFAULT: 'hsl(0 84% 60%)', foreground: '#ffffff' },
                success:     { DEFAULT: '#16a34a', foreground: '#ffffff' },
                'ocean-light': '#dde3ea',
                'ocean-deep':  '#0f172a',
                'warm-gold':   '#a28665',
                sand:          '#c4b39b',
                muted:  { DEFAULT: '#eeeeee', foreground: '#64748b' },
                border: { DEFAULT: '#e2e8f0' },
                surface: '#ffffff',
                foreground: '#0f172a',
                // Escala neutra realinhada ao Azul Intenso da marca (usada em dark mode)
                gray: {
                    50:  '#f4f6f8',
                    100: '#e7ebf0',
                    200: '#d3d9e0',
                    300: '#b0b9c4',
                    400: '#8993a3',
                    500: '#667385',
                    600: '#4c5870',
                    700: '#384357',
                    800: '#293b4f',
                    900: '#1e2a3a',
                    950: '#141c27',
                },
            },
            borderRadius: {
                DEFAULT: '0.75rem',
                lg: '0.75rem',
                md: '0.5rem',
                sm: '0.25rem',
            },
            boxShadow: {
                ocean: '0 10px 40px -10px hsl(200 75% 45% / 0.25)',
                card:  '0 4px 24px -4px hsl(210 30% 12% / 0.08)',
            },
        },
    },

    plugins: [forms, typography],
};

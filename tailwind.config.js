import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
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
                heading: ['Space Grotesk', ...defaultTheme.fontFamily.sans],
                body:    ['DM Sans', ...defaultTheme.fontFamily.sans],
                sans:    ['DM Sans', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary:     { DEFAULT: 'hsl(199 89% 48%)', foreground: '#ffffff', hover: 'hsl(199 89% 40%)', light: '#e0f2fe' },
                accent:      { DEFAULT: 'hsl(180 55% 40%)', foreground: '#ffffff' },
                destructive: { DEFAULT: 'hsl(0 84% 60%)',   foreground: '#ffffff' },
                'ocean-light': 'hsl(200 60% 90%)',
                'ocean-deep':  '#0f172a',
                'warm-gold':   'hsl(38 80% 55%)',
                sand:          'hsl(35 40% 85%)',
                muted:  { DEFAULT: '#f8fafc', foreground: '#64748b' },
                border: { DEFAULT: '#e2e8f0' },
                surface: '#ffffff',
                foreground: '#0f172a',
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

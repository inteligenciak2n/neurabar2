import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import globals from 'globals';

export default [
    { ignores: ['public/build/**', 'node_modules/**', 'vendor/**'] },
    js.configs.recommended,
    // "essential" only (bug-prevention), not "recommended" (adds ~150 stylistic
    // formatting rules that don't match this codebase's existing template style).
    ...pluginVue.configs['flat/essential'],
    {
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.node,
                route: 'readonly',
                Ziggy: 'readonly',
            },
        },
        rules: {
            // <script setup> silently drops references to names that were never
            // imported/declared instead of failing to compile — this is the guard-rail
            // for the class of bugs this project hit with __() missing useTranslate().
            'no-undef': 'error',
            'vue/multi-word-component-names': 'off',
            // Pre-existing unused imports/bindings across the app are a separate
            // cleanup, not something this lint setup should fail the build over.
            'no-unused-vars': 'warn',
        },
    },
];

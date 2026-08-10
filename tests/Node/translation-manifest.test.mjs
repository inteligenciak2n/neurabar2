import assert from 'node:assert/strict';
import { mkdtempSync, mkdirSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import test from 'node:test';
import { createTranslationManifest } from '../../scripts/translation-manifest.mjs';

test('builds page namespaces from recursive local Vue imports', () => {
    const root = mkdtempSync(join(tmpdir(), 'neurabar-translation-manifest-'));

    try {
        writeVue(root, 'Pages/Settings/Index.vue', `
            <script setup>
            import SettingsLayout from '@/Layouts/SettingsLayout.vue'
            import('@/Components/LazyPanel.vue')
            </script>
            <template><SettingsLayout>{{ __('Settings') }}</SettingsLayout></template>
        `);
        writeVue(root, 'Layouts/SettingsLayout.vue', `
            <script setup>
            import SharedButton from '@/Components/SharedButton.vue'
            </script>
            <template><SharedButton>{{ __('Layout') }}</SharedButton></template>
        `);
        writeVue(root, 'Components/SharedButton.vue', `
            <template><button>{{ __('Save') }}</button></template>
        `);
        writeVue(root, 'Components/LazyPanel.vue', `
            <template><aside>{{ __('Later') }}</aside></template>
        `);

        assert.deepEqual(createTranslationManifest(root), {
            'Settings/Index': ['Index', 'LazyPanel', 'SettingsLayout', 'SharedButton'],
        });
    } finally {
        rmSync(root, { recursive: true, force: true });
    }
});

test('keeps basename namespace collisions deduplicated', () => {
    const root = mkdtempSync(join(tmpdir(), 'neurabar-translation-manifest-'));

    try {
        writeVue(root, 'Pages/First/Index.vue', `<template>{{ __('First') }}</template>`);
        writeVue(root, 'Pages/Second/Index.vue', `<template>{{ __('Second') }}</template>`);

        assert.deepEqual(createTranslationManifest(root), {
            'First/Index': ['Index'],
            'Second/Index': ['Index'],
        });
    } finally {
        rmSync(root, { recursive: true, force: true });
    }
});

function writeVue(root, path, source) {
    const filePath = join(root, 'resources/js', path);
    const directory = filePath.slice(0, filePath.lastIndexOf('/'));

    mkdirSync(directory, { recursive: true });
    writeFileSync(filePath, source);
}
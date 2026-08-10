import { existsSync, readFileSync, readdirSync } from 'node:fs';
import { basename, dirname, extname, join, relative, resolve, sep } from 'node:path';
import { parse } from '@vue/compiler-sfc';

const IMPORT_PATTERN = /(?:import|export)\s+(?:[\s\S]*?\s+from\s+)?["']([^"']+)["']|import\(\s*["']([^"']+)["']\s*\)/g;

export function createTranslationManifest(projectRoot = process.cwd()) {
    const sourceRoot = join(projectRoot, 'resources/js');
    const pagesRoot = join(sourceRoot, 'Pages');
    const manifest = {};

    for (const pagePath of findVueFiles(pagesRoot)) {
        const pageName = relative(pagesRoot, pagePath)
            .split(sep)
            .join('/')
            .replace(/\.vue$/, '');

        manifest[pageName] = [...collectNamespaces(pagePath, sourceRoot)].sort();
    }

    return manifest;
}

function collectNamespaces(entryPath, sourceRoot, visited = new Set(), namespaces = new Set()) {
    const filePath = resolve(entryPath);

    if (visited.has(filePath) || ! existsSync(filePath)) {
        return namespaces;
    }

    visited.add(filePath);

    const source = readFileSync(filePath, 'utf8');
    const { descriptor } = parse(source, { filename: filePath });
    const componentSource = [
        descriptor.script?.content,
        descriptor.scriptSetup?.content,
        descriptor.template?.content,
    ].filter(Boolean).join('\n');

    if (componentSource.includes('__(') || componentSource.includes('useTranslate(')) {
        namespaces.add(basename(filePath, '.vue'));
    }

    for (const importPath of extractImports(componentSource)) {
        const dependency = resolveVueImport(importPath, filePath, sourceRoot);

        if (dependency) {
            collectNamespaces(dependency, sourceRoot, visited, namespaces);
        }
    }

    return namespaces;
}

function extractImports(source) {
    const imports = [];

    for (const match of source.matchAll(IMPORT_PATTERN)) {
        imports.push(match[1] ?? match[2]);
    }

    return imports;
}

function resolveVueImport(importPath, importerPath, sourceRoot) {
    if (! importPath.startsWith('.') && ! importPath.startsWith('@/')) {
        return null;
    }

    const candidate = importPath.startsWith('@/')
        ? join(sourceRoot, importPath.slice(2))
        : resolve(dirname(importerPath), importPath);

    for (const path of [candidate, `${candidate}.vue`, join(candidate, 'index.vue')]) {
        if (existsSync(path) && extname(path) === '.vue') {
            return path;
        }
    }

    return null;
}

function findVueFiles(directory) {
    if (! existsSync(directory)) {
        return [];
    }

    return readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
        const entryPath = join(directory, entry.name);

        if (entry.isDirectory()) {
            return findVueFiles(entryPath);
        }

        return entry.isFile() && entry.name.endsWith('.vue') ? [entryPath] : [];
    });
}
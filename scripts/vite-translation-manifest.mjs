import { createTranslationManifest } from './translation-manifest.mjs';

const virtualModuleId = 'virtual:translation-manifest';
const resolvedVirtualModuleId = `\0${virtualModuleId}`;

export function translationManifestPlugin() {
    let projectRoot;

    return {
        name: 'neurabar-translation-manifest',

        configResolved(config) {
            projectRoot = config.root;
        },

        resolveId(id) {
            return id === virtualModuleId ? resolvedVirtualModuleId : null;
        },

        load(id) {
            if (id !== resolvedVirtualModuleId) {
                return null;
            }

            return `export default ${JSON.stringify(createTranslationManifest(projectRoot))};`;
        },

        handleHotUpdate({ file, server }) {
            if (! file.endsWith('.vue')) {
                return;
            }

            const module = server.moduleGraph.getModuleById(resolvedVirtualModuleId);

            if (module) {
                server.moduleGraph.invalidateModule(module);
            }
        },
    };
}
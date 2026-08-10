import axios from 'axios';
import { reactive } from 'vue';

const state = reactive({
    locale: null,
    translationsByLocale: {},
});

const loadedByLocale = new Map();
const pendingByComponent = new Map();
const missingByComponent = new Map();
const queuedOrRegistered = new Set();
let missingFlushScheduled = false;

export function setTranslationLocale(locale) {
    if (! locale || state.locale === locale) {
        return;
    }

    state.locale = locale;
    pendingByComponent.clear();
    missingByComponent.clear();
    queuedOrRegistered.clear();
}

export async function ensureTranslations(components) {
    const requestedComponents = [...new Set(components.filter(Boolean))];
    const loaded = loadedComponents();
    const waiting = requestedComponents
        .map((component) => pendingByComponent.get(component))
        .filter(Boolean);
    const missing = requestedComponents.filter((component) => (
        ! loaded.has(component) && ! pendingByComponent.has(component)
    ));

    if (missing.length) {
        const requestedLocale = state.locale;
        const request = axios.get(route('api.translations.index'), {
            params: { components: missing },
        }).then(({ data }) => {
            if (requestedLocale && state.locale && requestedLocale !== state.locale) {
                return;
            }

            if (requestedLocale && data.locale !== requestedLocale) {
                return;
            }

            setTranslationLocale(data.locale);
            Object.assign(currentTranslations(), data.translations);

            const localeComponents = loadedComponents();
            missing.forEach((component) => localeComponents.add(component));
        }).finally(() => {
            missing.forEach((component) => {
                if (pendingByComponent.get(component) === request) {
                    pendingByComponent.delete(component);
                }
            });
        });

        missing.forEach((component) => pendingByComponent.set(component, request));
        waiting.push(request);
    }

    await Promise.all([...new Set(waiting)]);
}

export function translate(component, stringText, bindings = {}) {
    const translations = currentTranslations();
    const translation = translations[component]?.[stringText];

    if (translation !== undefined) {
        return applyBindings(translation, bindings);
    }

    if (loadedComponents().has(component)) {
        queueMissingTranslation(component, stringText);
    } else {
        ensureTranslations([component])
            .then(() => {
                if (currentTranslations()[component]?.[stringText] === undefined) {
                    queueMissingTranslation(component, stringText);
                }
            })
            .catch(() => {});
    }

    return applyBindings(stringText, bindings);
}

function loadedComponents() {
    const locale = state.locale ?? '__pending__';

    if (! loadedByLocale.has(locale)) {
        loadedByLocale.set(locale, new Set());
    }

    return loadedByLocale.get(locale);
}

function currentTranslations() {
    const locale = state.locale ?? '__pending__';

    if (! state.translationsByLocale[locale]) {
        state.translationsByLocale[locale] = {};
    }

    return state.translationsByLocale[locale];
}

function queueMissingTranslation(component, stringText) {
    const cacheKey = `${component}:${stringText}`;

    if (queuedOrRegistered.has(cacheKey)) {
        return;
    }

    queuedOrRegistered.add(cacheKey);

    if (! missingByComponent.has(component)) {
        missingByComponent.set(component, []);
    }

    missingByComponent.get(component).push(stringText);

    if (! missingFlushScheduled) {
        missingFlushScheduled = true;
        queueMicrotask(flushMissingTranslations);
    }
}

function flushMissingTranslations() {
    missingFlushScheduled = false;

    const batch = [...missingByComponent.entries()].slice(0, 50).map(([component, strings]) => {
        const selectedStrings = strings.splice(0, 100);

        if (strings.length === 0) {
            missingByComponent.delete(component);
        }

        return { component, strings: selectedStrings };
    });

    if (! batch.length) {
        return;
    }

    axios.post(route('api.translations.store'), { translations: batch })
        .catch(() => {
            batch.forEach(({ component, strings }) => {
                strings.forEach((stringText) => queuedOrRegistered.delete(`${component}:${stringText}`));
            });
        });

    if (missingByComponent.size) {
        missingFlushScheduled = true;
        queueMicrotask(flushMissingTranslations);
    }
}

function applyBindings(stringText, bindings) {
    return Object.entries(bindings).reduce(
        (translated, [key, value]) => translated.split(`:${key}`).join(value),
        stringText,
    );
}
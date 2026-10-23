import { usePage } from "@inertiajs/vue3"
import { getCurrentInstance } from "vue"
import { setTranslationLocale, translate, getTranslationRevision } from '@/Translations/translationStore'

export function useTranslate() {
    const instance = getCurrentInstance()
    const setupComponentName = instance?.type?.name ||
        instance?.type?.__name ||
        instance?.proxy?.$options?.name ||
        null

    const __ = ( stringText = null, bindings = {}, componentName = null ) => {
        if (!stringText) return ''

        const page = usePage()

        setTranslationLocale(page.props.language?.locale)

        void getTranslationRevision()

        const resolvedName = componentName || setupComponentName || 'UnknownComponent'

        return translate(resolvedName, stringText, bindings)
    }

    return __
}

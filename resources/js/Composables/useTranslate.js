import { usePage } from "@inertiajs/vue3"
import { getCurrentInstance } from "vue"
import { setTranslationLocale, translate } from '@/Translations/translationStore'

export function useTranslate() {
    const __ = ( stringText = null, bindings = {}, componentName = null ) => {
        if (!stringText) return ''

        const page = usePage()
        const instance = getCurrentInstance()

        setTranslationLocale(page.props.language?.locale)

        componentName = componentName || instance?.type?.name || 
                              instance?.type?.__name || 
                              instance?.proxy?.$options?.name ||
                              'UnknownComponent'

        return translate(componentName, stringText, bindings)
    }

    return __
}
import axios from "axios"
import { usePage } from "@inertiajs/vue3"
import { getCurrentInstance } from "vue"

// Cache para evitar chamadas repetidas
const translationCache = new Set()

export function useTranslate() {

    const __ = ( stringText = null, bindings = {} ) => {
        if (!stringText) return ''

        const page = usePage()
        const instance = getCurrentInstance()

        // Nome do componente
        const componentName = instance?.type?.name || 
                              instance?.type?.__name || 
                              instance?.proxy?.$options?.name ||
                              'UnknownComponent'

        // Verificar se já existe tradução
        if( 
            page.props.language?.lang?.hasOwnProperty(componentName) 
            && page.props.language.lang[componentName].hasOwnProperty(stringText) 
        ){
            return getBindings( page.props.language.lang[componentName][stringText], bindings )
        }

        // Enviar para registro apenas uma vez usando cache
        const cacheKey = `${componentName}:${stringText}`
        if (!translationCache.has(cacheKey)) {
            translationCache.add(cacheKey)
            // Enviar de forma assíncrona sem bloquear
            setTimeout(() => {
                axios.post(route('api.set.translations', { page: componentName }), { value: stringText })
                    .catch(() => {}) // Ignorar erros silenciosamente
            }, 0)
        }
        
        return stringText
    }

    const translatePage = ( stringText = null, bindings = {} ) => {
        if (!stringText) return ''

        const page = usePage()

        if( page.props.language?.lang?.hasOwnProperty(stringText) ){
            return getBindings( page.props.language.lang[stringText], bindings )
        }

        // Enviar para registro apenas uma vez usando cache
        const cacheKey = `page:${stringText}`
        if (!translationCache.has(cacheKey)) {
            translationCache.add(cacheKey)
            setTimeout(() => {
                axios.post(route('api.set.translations', { page: route().current() }), { value: stringText })
                    .catch(() => {})
            }, 0)
        }
        
        return stringText
    }

    const trans = ( stringText = null, bindings = {} ) => {
        return __( stringText, bindings )
    }
 
    //usage sample:  __('Dashboard, olá :username', { username: $page.props.auth.user.name })
    const getBindings = ( stringText = null, bindings = {} ) => {
        Object.keys(bindings).forEach( key => {
            stringText = stringText.replace( `:${key}`, bindings[key] )
        })
        return stringText
    }

    return __ , trans
}
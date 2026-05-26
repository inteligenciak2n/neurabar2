import { useTranslate } from "@/Composables/useTranslate"

export default {
    install: (app, options) => {
        const __ = ( stringText = null, bindings = {} ) => {
            return useTranslate()(stringText, bindings)
        }

        // Para Options API - adiciona às globalProperties
        app.config.globalProperties.__ = __

        // Para Composition API - provide/inject
        app.provide('__', __)

        // Mixin para Options API (não afeta Composition API)
        app.mixin({
            beforeCreate() {
                if (!this.$options.setup) { // Só aplica se não for Composition API
                    this.__ = __
                }
            }
        })
    }
}
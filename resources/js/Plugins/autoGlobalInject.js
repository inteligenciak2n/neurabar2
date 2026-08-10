import { useTranslate } from "@/Composables/useTranslate"

export default {
    install: (app) => {
        const __ = ( stringText = null, bindings = {}, componentName = null ) => {
            return useTranslate()(stringText, bindings, componentName)
        }

        app.config.globalProperties.__ = __
        app.provide('__', __)
        app.mixin({
            beforeCreate() {
                if (!this.$options.setup) {
                    this.__ = __
                }
            }
        })
    }
}
import { onBeforeUnmount, watch } from 'vue';
import { watchDebounced } from '@vueuse/core';
import { useSettingsEditing } from '@/Composables/useSettingsEditing';

export function useAutosaveForm(form, save, debounceMs = 500) {
    const { editingEnabled } = useSettingsEditing();

    const persist = () => {
        if (!form.isDirty || form.processing) {
            return;
        }

        save();
    };

    watchDebounced(
        form,
        () => {
            if (!editingEnabled.value) {
                return;
            }

            persist();
        },
        { deep: true, debounce: debounceMs, maxWait: 2000 },
    );

    watch(editingEnabled, (enabled, wasEnabled) => {
        if (wasEnabled && !enabled) {
            persist();
        }
    });

    onBeforeUnmount(() => {
        if (editingEnabled.value) {
            persist();
        }
    });
}

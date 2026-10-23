import { onBeforeUnmount, watch } from 'vue';
import { useSettingsEditing } from '@/Composables/useSettingsEditing';

export function useAutosaveForm(form, save) {
    const { editingEnabled, registerAutosave } = useSettingsEditing();

    const persist = ({ ignoreEditing = false } = {}) => {
        if ((!ignoreEditing && !editingEnabled.value) || !form.isDirty || form.processing) {
            return;
        }

        save();
    };

    registerAutosave(() => persist());

    watch(editingEnabled, (enabled, wasEnabled) => {
        if (wasEnabled && !enabled) {
            persist({ ignoreEditing: true });
        }
    });

    onBeforeUnmount(() => {
        persist({ ignoreEditing: true });
    });

    return { persist };
}

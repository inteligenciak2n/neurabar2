import { onBeforeUnmount, ref } from 'vue';

const editingEnabled = ref(false);
const savers = new Set();

export function useSettingsEditing() {
    const enableEditing = () => {
        editingEnabled.value = true;
    };

    const registerAutosave = (save) => {
        savers.add(save);

        onBeforeUnmount(() => {
            savers.delete(save);
        });
    };

    const flushAutosave = () => {
        savers.forEach((save) => save());
    };

    return { editingEnabled, enableEditing, registerAutosave, flushAutosave };
}

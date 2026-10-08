import { ref } from 'vue';

const editingEnabled = ref(false);

export function useSettingsEditing() {
    const enableEditing = () => {
        editingEnabled.value = true;
    };

    return { editingEnabled, enableEditing };
}

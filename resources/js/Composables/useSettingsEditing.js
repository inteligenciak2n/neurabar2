import { ref } from 'vue';

const editingEnabled = ref(false);

export function useSettingsEditing() {
    return { editingEnabled };
}

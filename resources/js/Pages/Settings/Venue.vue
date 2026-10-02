<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    venue: Object,
});

const form = useForm({
    name: props.venue.name ?? '',
    description: props.venue.description ?? '',
    tax_id: props.venue.tax_id ?? '',
    phone: props.venue.phone ?? '',
    whatsapp_agent: props.venue.whatsapp_agent ?? '',
    street: props.venue.street ?? '',
    number: props.venue.number ?? '',
    complement: props.venue.complement ?? '',
    neighborhood: props.venue.neighborhood ?? '',
    city: props.venue.city ?? '',
    state: props.venue.state ?? '',
    zip_code: props.venue.zip_code ?? '',
    timezone: props.venue.timezone ?? '',
    require_table: props.venue.require_table ?? false,
    require_tab: props.venue.require_tab ?? false,
    require_location: props.venue.require_location ?? false,
    logo: null,
    remove_logo: false,
    latitude: props.venue.latitude ?? '',
    longitude: props.venue.longitude ?? '',
    require_geolocation: props.venue.require_geolocation ?? false,
});

const logoInput = ref(null);
const logoPreview = ref(props.venue.logo_url || null);
const isDraggingLogo = ref(false);
const logoFailed = ref(false);

const showLogoPreview = computed(() => Boolean(logoPreview.value) && !logoFailed.value);
const canRemoveLogo = computed(() => {
    if (form.remove_logo && !form.logo) {
        return false;
    }

    return Boolean(form.logo || props.venue.logo_url);
});

const revokeLogoPreview = () => {
    if (logoPreview.value?.startsWith('blob:')) {
        URL.revokeObjectURL(logoPreview.value);
    }
};

const assignLogo = (file) => {
    if (!file || !file.type.startsWith('image/')) {
        return;
    }

    revokeLogoPreview();
    form.logo = file;
    form.remove_logo = false;
    logoFailed.value = false;
    logoPreview.value = URL.createObjectURL(file);
};

const clearLogo = (event) => {
    event.stopPropagation();
    revokeLogoPreview();
    logoPreview.value = null;
    isDraggingLogo.value = false;
    form.logo = null;
    form.remove_logo = true;
    logoFailed.value = false;
    if (logoInput.value) {
        logoInput.value.value = '';
    }
};

const onLogoSelect = (event) => {
    assignLogo(event.target.files?.[0] ?? null);
};

const onLogoDrop = (event) => {
    isDraggingLogo.value = false;
    assignLogo(event.dataTransfer.files?.[0] ?? null);
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        require_table: data.require_table ? 1 : 0,
        require_tab: data.require_tab ? 1 : 0,
        require_location: data.require_location ? 1 : 0,
        require_geolocation: data.require_geolocation ? 1 : 0,
        logo: data.logo || null,
        remove_logo: data.remove_logo ? 1 : 0,
        _method: 'put',
    }));
    form.post(route('settings.venue.update'), {
        forceFormData: true,
        onFinish: () => {
            form.transform((data) => data);
        },
    });
};
</script>

<template>
    <SettingsLayout :title="__('Venue Settings')">
        <template #header>
            <h1 class="font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Venue Settings') }}</h1>
        </template>

        <form @submit.prevent="submit">
            <div class="space-y-6">
                <AppCard :title="__('Basic Information')">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Name') }} <span class="text-destructive">*</span></label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Tax ID') }}</label>
                            <input
                                v-model="form.tax_id"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Phone') }}</label>
                            <input
                                v-model="form.phone"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('WhatsApp Agent') }}</label>
                            <input
                                v-model="form.whatsapp_agent"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Timezone') }}</label>
                            <input
                                v-model="form.timezone"
                                type="text"
                                placeholder="America/Sao_Paulo"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Establishment description') }}</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100"
                            />
                            <p class="mt-1 text-xs text-muted-foreground">{{ __('Shown on the customer menu. Write a short, inviting description of your venue.') }}</p>
                            <p v-if="form.errors.description" class="mt-1 text-xs text-destructive">{{ form.errors.description }}</p>
                        </div>
                    </div>
                </AppCard>

                <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_12rem]">
                    <AppCard :title="__('Address')">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Street') }}</label>
                            <input
                                v-model="form.street"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Number') }}</label>
                            <input
                                v-model="form.number"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Complement') }}</label>
                            <input
                                v-model="form.complement"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Neighborhood') }}</label>
                            <input
                                v-model="form.neighborhood"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('City') }}</label>
                            <input
                                v-model="form.city"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('State') }}</label>
                            <input
                                v-model="form.state"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('ZIP Code') }}</label>
                            <input
                                v-model="form.zip_code"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>
                    </div>
                </AppCard>

                <AppCard :title="__('Logo')">
                    <div
                        class="relative aspect-square cursor-pointer overflow-hidden rounded-md border-2 border-dashed px-1.5 text-center transition-colors"
                        :class="isDraggingLogo
                            ? 'border-primary bg-primary/5 dark:bg-primary/10'
                            : 'border-border bg-muted/40 dark:border-gray-600 dark:bg-gray-800/60'"
                        @click="logoInput?.click()"
                        @dragenter.prevent="isDraggingLogo = true"
                        @dragover.prevent="isDraggingLogo = true"
                        @dragleave.prevent="isDraggingLogo = false"
                        @drop.prevent="onLogoDrop"
                    >
                        <input
                            ref="logoInput"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            class="hidden"
                            @change="onLogoSelect"
                            @click.stop
                        />
                        <img
                            v-if="showLogoPreview"
                            :src="logoPreview"
                            :alt="form.name || __('Logo')"
                            class="pointer-events-none absolute inset-0 h-full w-full object-contain"
                            @error="logoFailed = true"
                        />
                        <div
                            v-if="showLogoPreview"
                            class="pointer-events-none absolute inset-0 bg-black/35"
                        />
                        <p
                            class="pointer-events-none relative z-10 text-[11px] leading-tight"
                            :class="showLogoPreview ? 'text-white' : 'text-muted-foreground dark:text-gray-400'"
                        >
                            {{ showLogoPreview
                                ? __('Drop a new photo to replace it, or click to choose.')
                                : __('Drag a photo here or click to upload.') }}
                        </p>
                    </div>
                    <p class="mt-2 text-[11px] leading-tight text-muted-foreground dark:text-gray-400">{{ __('Format: JPEG, PNG or WebP') }}</p>
                    <p v-if="form.errors.logo" class="mt-1 text-xs text-destructive">{{ form.errors.logo }}</p>
                    <AppButton
                        type="button"
                        size="sm"
                        variant="destructive"
                        class="mt-2 w-full"
                        :disabled="!canRemoveLogo"
                        @click.stop="clearLogo"
                    >
                        {{ __('Delete photo') }}
                    </AppButton>
                </AppCard>
                </div>

                <AppCard :title="__('Operational Requirements')">
                    <div class="space-y-3">
                        <label class="flex cursor-pointer items-center gap-3">
                            <input v-model="form.require_table" type="checkbox" class="h-4 w-4 rounded border-border dark:border-gray-700 text-primary focus:ring-primary" />
                            <span class="text-sm text-ocean-deep dark:text-gray-100">{{ __('Require table number on orders') }}</span>
                        </label>

                        <label class="flex cursor-pointer items-center gap-3">
                            <input v-model="form.require_tab" type="checkbox" class="h-4 w-4 rounded border-border dark:border-gray-700 text-primary focus:ring-primary" />
                            <span class="text-sm text-ocean-deep dark:text-gray-100">{{ __('Require tab (customer name) on orders') }}</span>
                        </label>

                        <label class="flex cursor-pointer items-center gap-3">
                            <input v-model="form.require_location" type="checkbox" class="h-4 w-4 rounded border-border dark:border-gray-700 text-primary focus:ring-primary" />
                            <span class="text-sm text-ocean-deep dark:text-gray-100">{{ __('Require service location on orders') }}</span>
                        </label>
                    </div>
                </AppCard>

                <AppCard :title="__('Geolocation')">                    <div class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Latitude') }}</label>
                                <input
                                    v-model="form.latitude"
                                    type="number"
                                    step="any"
                                    placeholder="-23.5505"
                                    class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Longitude') }}</label>
                                <input
                                    v-model="form.longitude"
                                    type="number"
                                    step="any"
                                    placeholder="-46.6333"
                                    class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                                />
                            </div>
                        </div>
                        <label class="flex cursor-pointer items-center gap-3">
                            <input v-model="form.require_geolocation" type="checkbox" class="h-4 w-4 rounded border-border dark:border-gray-700 text-primary focus:ring-primary" />
                            <span class="text-sm text-ocean-deep dark:text-gray-100">{{ __('Require guest geolocation to place orders') }}</span>
                        </label>
                        <p class="text-xs text-muted-foreground">{{ __('Guests will be asked for their location when accessing via QR code. Only allow orders when within 200m.') }}</p>
                    </div>
                </AppCard>

                <div class="flex justify-end">
                    <AppButton type="submit" :loading="form.processing">{{ __('Save Changes') }}</AppButton>
                </div>
            </div>
        </form>
    </SettingsLayout>
</template>

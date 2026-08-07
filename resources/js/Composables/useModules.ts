import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export interface Tenant {
  id: string;
  name: string;
  modules: string[];
  role: string | null;
  blocked: boolean;
}

export function useModules() {
  const page = usePage();

  // HandleInertiaRequests nests tenant under the `defs` bag, so reading
  // `page.props.tenant` always resolved to undefined and hid every module.
  const tenant = computed<Tenant | null>(
    () => ((page.props.defs as Record<string, unknown> | undefined)?.tenant as Tenant | null) ?? null,
  );

  const modules = computed<string[]>(() => tenant.value?.modules ?? []);

  const hasModule = (code: string): boolean => modules.value.includes(code);

  const isBlocked = computed<boolean>(() => tenant.value?.blocked ?? false);

  return {
    tenant,
    modules,
    hasModule,
    isBlocked,
  };
}

<?php

namespace App\Rules;

use App\Enums\ModuleCode;
use App\Models\Tenant\ModuleCatalog;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates a whole set of module codes at once: every code must exist, be
 * active in the catalog, and have its dependencies satisfied by the same set.
 *
 * Validating code by code with `exists:module_catalogs,code` lets an inactive
 * module or a module missing its dependency be contracted and billed, only to
 * be denied at runtime by `ModuleAccessService`.
 */
class ContractableModules implements ValidationRule
{
    /**
     * @param  list<ModuleCode>  $implicitlyGranted  Modules always provisioned by the caller.
     */
    public function __construct(private readonly array $implicitlyGranted = [ModuleCode::Menu]) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === []) {
            return;
        }

        if (! is_array($value)) {
            $fail(__('The :attribute must be a list of module codes.'));

            return;
        }

        $requested = [];

        foreach ($value as $code) {
            $module = is_string($code) ? ModuleCode::tryFrom($code) : null;

            if (! $module instanceof ModuleCode) {
                $fail(__('The module :code does not exist.'))->translate(['code' => (string) $code]);

                return;
            }

            $requested[] = $module;
        }

        $inactive = ModuleCatalog::query()
            ->whereIn('code', array_map(fn (ModuleCode $module) => $module->value, $requested))
            ->where('active', false)
            ->pluck('code')
            ->all();

        if ($inactive !== []) {
            $fail(__('The module :code is not available for contracting.'))
                ->translate(['code' => implode(', ', $inactive)]);

            return;
        }

        $available = array_map(
            fn (ModuleCode $module) => $module->value,
            [...$this->implicitlyGranted, ...$requested],
        );

        foreach ($requested as $module) {
            foreach ($module->dependsOn() as $dependency) {
                if (! in_array($dependency->value, $available, true)) {
                    $fail(__('The module :code requires :dependency.'))->translate([
                        'code' => $module->value,
                        'dependency' => $dependency->value,
                    ]);

                    return;
                }
            }
        }
    }
}

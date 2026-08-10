<?php

namespace App\Http\Requests\Platform;

use App\Enums\ModuleCode;
use App\Enums\ProfileEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePlanCatalogVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->profile, [ProfileEnum::SuperAdmin, ProfileEnum::Finance], true);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'effective_from' => ['required', 'date_format:Y-m-d', 'after_or_equal:tomorrow'],
            'minimum_monthly_price' => ['required', 'numeric', 'min:0'],
            'infrastructure_type' => ['required', Rule::in(['shared', 'dedicated'])],
            'currency' => ['required', 'string', 'size:3'],
            'tiers' => ['required', 'array', 'min:1'],
            'tiers.*.module_code' => ['required', Rule::enum(ModuleCode::class)],
            'tiers.*.min_quantity' => ['required', 'integer', 'min:0'],
            'tiers.*.max_quantity' => ['nullable', 'integer', 'min:0'],
            'tiers.*.included_quantity' => ['required', 'integer', 'min:0'],
            'tiers.*.price_per_unit' => ['required', 'numeric', 'min:0'],
            'tiers.*.flat_price' => ['nullable', 'numeric', 'min:0'],
            'tiers.*.overage_price_per_unit' => ['required', 'numeric', 'min:0'],
            'tiers.*.overage_flat_fee' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $tiersByModule = collect($this->input('tiers', []))->groupBy('module_code');

            foreach ($tiersByModule as $moduleCode => $tiers) {
                $ordered = $tiers->sortBy(fn (array $tier): int => (int) $tier['min_quantity'])->values();

                if ((int) $ordered->first()['min_quantity'] !== 0) {
                    $validator->errors()->add('tiers', "As faixas de {$moduleCode} devem iniciar em zero.");
                }

                foreach ($ordered as $index => $tier) {
                    $minimum = (int) $tier['min_quantity'];
                    $maximum = $tier['max_quantity'] === null || $tier['max_quantity'] === ''
                        ? null
                        : (int) $tier['max_quantity'];

                    if ($maximum !== null && $maximum < $minimum) {
                        $validator->errors()->add('tiers', "A faixa de {$moduleCode} possui máximo menor que o mínimo.");
                    }

                    if ($index === 0) {
                        continue;
                    }

                    $previousMaximum = $ordered[$index - 1]['max_quantity'];

                    if ($previousMaximum === null || $previousMaximum === '' || $minimum !== (int) $previousMaximum + 1) {
                        $validator->errors()->add('tiers', "As faixas de {$moduleCode} possuem lacuna ou sobreposição.");
                    }
                }
            }
        });
    }
}

<?php

namespace App\Http\Requests\Platform;

use App\Enums\ModuleCode;
use App\Enums\ProfileEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreVenueUsageTierOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->profile, [ProfileEnum::SuperAdmin, ProfileEnum::Finance], true);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'venue_plan_assignment_id' => ['required', 'uuid'],
            'module_code' => ['required', Rule::enum(ModuleCode::class)],
            'tiers' => ['required', 'array', 'min:1'],
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
            $tiers = collect($this->input('tiers', []))
                ->sortBy(fn (array $tier): int => (int) $tier['min_quantity'])
                ->values();

            if ($tiers->isEmpty()) {
                return;
            }

            if ((int) $tiers->first()['min_quantity'] !== 0) {
                $validator->errors()->add('tiers', __('Override tiers must start at zero.'));
            }

            foreach ($tiers as $index => $tier) {
                $minimum = (int) $tier['min_quantity'];
                $maximum = $tier['max_quantity'] === null || $tier['max_quantity'] === '' ? null : (int) $tier['max_quantity'];

                if ($maximum !== null && $maximum < $minimum) {
                    $validator->errors()->add('tiers', __('A tier maximum cannot be lower than its minimum.'));
                }

                if ($index > 0) {
                    $previousMaximum = $tiers[$index - 1]['max_quantity'];

                    if ($previousMaximum === null || $previousMaximum === '' || $minimum !== (int) $previousMaximum + 1) {
                        $validator->errors()->add('tiers', __('Override tiers cannot have gaps or overlaps.'));
                    }
                }
            }

            $lastMaximum = $tiers->last()['max_quantity'];

            if ($lastMaximum !== null && $lastMaximum !== '') {
                $validator->errors()->add('tiers', __('The last override tier must be open-ended.'));
            }
        });
    }
}

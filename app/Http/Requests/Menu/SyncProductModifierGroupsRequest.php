<?php

namespace App\Http\Requests\Menu;

use App\Models\Menu\ModifierGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncProductModifierGroupsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $venue = app('tenant');

        $venueGroupIds = ModifierGroup::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->pluck('id')
            ->all();

        return [
            'modifier_group_ids' => ['present', 'array'],
            'modifier_group_ids.*' => ['uuid', Rule::in($venueGroupIds)],
        ];
    }
}

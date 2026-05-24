<?php

namespace App\Http\Requests\Menu;

use App\Models\Menu\Menu;
use App\Models\Settings\KitchenStation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $venue = app('tenant');

        $menuIds = Menu::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->pluck('id')
            ->all();

        $stationIds = KitchenStation::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->pluck('id')
            ->all();

        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'category_id' => ['required', 'uuid', Rule::exists('menu_categories', 'id')->whereIn('menu_id', $menuIds)],
            'kitchen_station_id' => ['nullable', 'uuid', Rule::in($stationIds)],
            'description' => ['nullable', 'string', 'max:1000'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'active' => ['boolean'],
        ];
    }
}

<?php

namespace App\Http\Requests\Orders;

use App\Models\Settings\Tab;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LinkAttendanceTabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $venueId = app('tenant')->id;

        return [
            'tab_id' => [
                'required',
                'uuid',
                Rule::exists(Tab::class, 'id')->where('venue_id', $venueId)->where('active', true),
            ],
        ];
    }
}

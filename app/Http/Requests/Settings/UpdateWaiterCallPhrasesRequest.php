<?php

namespace App\Http\Requests\Settings;

use App\Models\Settings\VenueSettings;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWaiterCallPhrasesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'phrases' => ['present', 'array', 'max:'.VenueSettings::MAX_WAITER_CALL_PHRASES],
            'phrases.*' => ['required', 'string', 'max:60'],
        ];
    }
}

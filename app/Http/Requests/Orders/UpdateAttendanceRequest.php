<?php

namespace App\Http\Requests\Orders;

use App\Models\Settings\ServiceLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'customer_identifier' => ['nullable', 'string', 'max:255'],
            'party_size' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'service_location_id' => [
                'nullable',
                'uuid',
                Rule::exists((new ServiceLocation)->getConnectionName().'.service_locations', 'id')->where('venue_id', app('tenant')->id),
            ],
        ];
    }
}

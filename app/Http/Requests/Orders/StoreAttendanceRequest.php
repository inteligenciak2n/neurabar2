<?php

namespace App\Http\Requests\Orders;

use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\ServiceLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $venue = app('tenant');

        return [
            'attendance_channel_id' => [
                'required',
                'uuid',
                Rule::exists(AttendanceChannel::class, 'id')
                    ->where('venue_id', $venue->id)
                    ->where('active', true),
            ],
            'customer_identifier' => ['nullable', 'string', 'max:255'],
            'service_location_id' => ['nullable', 'uuid', Rule::exists(ServiceLocation::class, 'id')],
            'party_size' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

<?php

namespace App\Http\Requests\Orders;

use App\Models\Settings\AttendanceChannel;
use Illuminate\Foundation\Http\FormRequest;

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

        $validChannels = AttendanceChannel::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->where('active', true)
            ->pluck('value')
            ->toArray();

        return [
            'channel' => ['required', 'string', 'in:'.implode(',', $validChannels)],
            'customer_identifier' => ['nullable', 'string', 'max:255'],
            'service_location_id' => ['nullable', 'uuid', 'exists:service_locations,id'],
            'party_size' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

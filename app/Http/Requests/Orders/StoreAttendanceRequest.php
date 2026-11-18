<?php

namespace App\Http\Requests\Orders;

use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\ServiceLocation;
use App\Models\Settings\Tab;
use App\Rules\AvailableTabCode;
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
            'code' => ['nullable', 'string', 'max:32', 'regex:/^[A-Za-z0-9._-]+$/', new AvailableTabCode($venue->id)],
            'tab_id' => ['nullable', 'uuid', Rule::exists(Tab::class, 'id')->where('venue_id', $venue->id)],
            'service_location_id' => ['nullable', 'uuid', Rule::exists(ServiceLocation::class, 'id')],
            'party_size' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

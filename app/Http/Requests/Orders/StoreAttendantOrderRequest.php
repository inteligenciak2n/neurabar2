<?php

namespace App\Http\Requests\Orders;

use App\Models\Orders\Attendance;
use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\DeliveryFeeZone;
use App\Models\Settings\ServiceLocation;
use App\Models\Settings\Tab;
use Illuminate\Validation\Rule;

class StoreAttendantOrderRequest extends StoreOrderRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $venue = app('tenant');

        return array_merge(parent::rules(), [
            'attendance_id' => [
                'nullable',
                'uuid',
                Rule::exists(Attendance::class, 'id')->where('venue_id', $venue->id),
            ],
            'attendance_channel_id' => [
                'required',
                'uuid',
                Rule::exists(AttendanceChannel::class, 'id')
                    ->where('venue_id', $venue->id)
                    ->where('active', true),
            ],
            'tab_id' => ['nullable', 'uuid', Rule::exists(Tab::class, 'id')->where('venue_id', $venue->id)],
            'service_location_id' => [
                'nullable',
                'uuid',
                Rule::exists(ServiceLocation::class, 'id'),
            ],
            'customer_identifier' => ['nullable', 'string', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'customer_address' => ['nullable', 'string', 'max:500'],
            'customer_comment' => ['nullable', 'string', 'max:500'],
            'delivery_address' => ['nullable', 'array'],
            'delivery_address.zip_code' => ['nullable', 'string', 'max:9'],
            'delivery_address.street' => ['nullable', 'string', 'max:255'],
            'delivery_address.number' => ['nullable', 'string', 'max:30'],
            'delivery_address.complement' => ['nullable', 'string', 'max:255'],
            'delivery_address.delivery_fee_zone_id' => [
                'nullable',
                'uuid',
                Rule::exists(DeliveryFeeZone::class, 'id')->where('venue_id', $venue->id),
            ],
            'delivery_address.neighborhood' => ['nullable', 'string', 'max:255'],
            'delivery_address.delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'delivery_address.city' => ['nullable', 'string', 'max:255'],
            'delivery_address.state' => ['nullable', 'string', 'max:2'],
            'delivery_address.reference_point' => ['nullable', 'string', 'max:500'],
            'delivery_address.delivery_phone' => ['nullable', 'string', 'max:20'],
            'party_size' => ['nullable', 'integer', 'min:1'],
        ]);
    }
}

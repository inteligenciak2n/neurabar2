<?php

namespace App\Http\Requests\Orders;

use App\Models\Settings\ServiceLocation;
use App\Rules\AvailableTabCode;
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
        // Delivery/pickup orders are identified by delivery_orders.code, never by a tab card.
        $isDeliveryAttendance = (bool) $this->route('attendance')?->deliveryOrder()->exists();

        return [
            'customer_identifier' => ['nullable', 'string', 'max:255'],
            'code' => [
                'nullable',
                $isDeliveryAttendance ? 'prohibited' : 'string',
                'max:32',
                'regex:/^[A-Za-z0-9._-]+$/',
                new AvailableTabCode(app('tenant')->id, $this->route('attendance')?->id),
            ],
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

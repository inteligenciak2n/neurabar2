<?php

namespace App\Http\Requests\Orders;

use App\Models\Settings\AttendanceChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendantServiceLocationRequest extends FormRequest
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
            'number' => ['required', 'integer', 'min:1', 'max:99999'],
        ];
    }
}

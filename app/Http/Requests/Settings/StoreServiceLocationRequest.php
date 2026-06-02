<?php

namespace App\Http\Requests\Settings;

use App\Enums\ServiceLocationType;
use App\Models\Settings\AttendanceChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreServiceLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', new Enum(ServiceLocationType::class)],
            'active' => ['boolean'],
            'default_attendance_channel_id' => ['nullable', 'uuid', Rule::exists(AttendanceChannel::class, 'id')],
        ];
    }
}

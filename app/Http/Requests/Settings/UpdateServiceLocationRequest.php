<?php

namespace App\Http\Requests\Settings;

use App\Models\Settings\AttendanceChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceLocationRequest extends FormRequest
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
            'active' => ['boolean'],
            'default_attendance_channel_id' => ['nullable', 'uuid', Rule::exists(AttendanceChannel::class, 'id')],
        ];
    }
}

<?php

namespace App\Http\Requests\Settings;

use App\Models\Settings\AttendanceChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceLocationRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'from' => ['required', 'integer', 'min:1', 'max:99999'],
            'to' => ['required', 'integer', 'min:1', 'max:99999', 'gte:from'],
            'default_attendance_channel_id' => ['required', 'uuid', Rule::exists(AttendanceChannel::class, 'id')],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $from = (int) $this->input('from');
            $to = (int) $this->input('to');

            if ($to - $from + 1 > 500) {
                $validator->errors()->add('to', 'A range cannot create more than 500 locations at once.');
            }
        });
    }
}

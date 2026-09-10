<?php

namespace App\Http\Requests\Guest;

use Illuminate\Foundation\Http\FormRequest;

class VerifyDeliveryPhoneOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'reference_id' => ['required', 'string'],
            'code' => ['required', 'string', 'max:10'],
        ];
    }
}

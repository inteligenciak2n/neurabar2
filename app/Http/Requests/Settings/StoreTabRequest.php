<?php

namespace App\Http\Requests\Settings;

use App\Models\Settings\Tab;
use App\Support\TabCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('code')) {
            $this->merge([
                'code' => strtoupper(trim((string) $this->input('code'))),
            ]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $venue = app('tenant');

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:'.TabCode::CODE_REGEX,
                Rule::unique(Tab::class, 'code')->where('venue_id', $venue->id),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'code.regex' => 'Use only letters, numbers and a hyphen.',
            'code.unique' => 'This tab code is already in use.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $code = (string) $this->input('code');

            if ($code === '' || TabCode::hasNumber($code)) {
                return;
            }

            $validator->errors()->forget('code');
            $validator->errors()->add('code', 'A tab code must include a number.');
        });
    }
}

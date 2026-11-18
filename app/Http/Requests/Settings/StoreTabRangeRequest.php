<?php

namespace App\Http\Requests\Settings;

use App\Support\TabCode;
use Illuminate\Foundation\Http\FormRequest;

class StoreTabRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'prefix' => TabCode::normalizePrefix($this->input('prefix')),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'prefix' => ['nullable', 'string', 'max:10', 'regex:/^[A-Z0-9]*$/'],
            'from' => ['required', 'integer', 'min:1', 'max:99999'],
            'to' => ['required', 'integer', 'min:1', 'max:99999', 'gte:from'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $from = (int) $this->input('from');
            $to = (int) $this->input('to');

            if ($to - $from + 1 > 500) {
                $validator->errors()->add('to', 'A range cannot create more than 500 tabs at once.');
            }

            $prefix = TabCode::normalizePrefix($this->input('prefix'));
            $longest = TabCode::compose($prefix, $to);

            if (strlen($longest) > 20) {
                $validator->errors()->add('prefix', 'The prefix plus the numbers cannot exceed 20 characters.');
            }
        });
    }
}

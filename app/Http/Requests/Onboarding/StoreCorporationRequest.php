<?php

namespace App\Http\Requests\Onboarding;

use App\Rules\BrazilianState;
use App\Rules\ValidTaxId;
use App\Rules\ValidTimezone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCorporationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->onboarding_completed_at === null
            && $user->ownedCorporation !== null;
    }

    /**
     * `skip` only arrives from the wizard when the checkbox is checked, so an
     * omitted key left the value as `null` and silently bypassed the guard on
     * `name`, blowing up with `Undefined array key 'name'` on the very last
     * step of the funnel.
     */
    protected function prepareForValidation(): void
    {
        $venues = $this->input('venues');

        if (! is_array($venues)) {
            return;
        }

        $this->merge([
            'venues' => array_map(function ($venue) {
                if (! is_array($venue)) {
                    return $venue;
                }

                $venue['skip'] = filter_var($venue['skip'] ?? false, FILTER_VALIDATE_BOOLEAN);

                return $venue;
            }, $venues),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => [
                'nullable',
                'string',
                'max:20',
                new ValidTaxId,
                Rule::unique('saas.corporations', 'tax_id')
                    ->ignore($this->user()?->ownedCorporation?->id),
            ],
            // The billing e-mail is the only channel used to deliver invoices and
            // dunning notices; without it the customer gets suspended without ever
            // having been told there was a charge.
            'email' => ['required', 'string', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],

            'venues' => ['required', 'array', 'min:1', 'max:20'],
            'venues.*.skip' => ['required', 'boolean'],
            'venues.*.name' => ['required_unless:venues.*.skip,true', 'nullable', 'string', 'max:255'],
            'venues.*.tax_id' => ['nullable', 'string', 'max:20', new ValidTaxId],
            'venues.*.phone' => ['nullable', 'string', 'max:20'],
            'venues.*.city' => ['nullable', 'string', 'max:100'],
            'venues.*.state' => ['nullable', 'string', 'size:2', new BrazilianState],
            'venues.*.timezone' => ['nullable', 'string', 'max:50', new ValidTimezone],
        ];
    }
}

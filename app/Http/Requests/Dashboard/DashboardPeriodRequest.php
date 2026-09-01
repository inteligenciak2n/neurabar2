<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class DashboardPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'period' => ['nullable', 'string', 'in:today,7d,30d,month,custom'],
            'from' => ['required_if:period,custom', 'date'],
            'to' => ['required_if:period,custom', 'date', 'after_or_equal:from'],
            'scope' => ['nullable', 'string', 'in:venue,corporation'],
        ];
    }

    public function period(): string
    {
        return $this->input('period', '30d');
    }

    public function fromDate(): ?string
    {
        return $this->input('from');
    }

    public function toDate(): ?string
    {
        return $this->input('to');
    }

    public function scope(): string
    {
        return $this->input('scope', 'venue');
    }
}

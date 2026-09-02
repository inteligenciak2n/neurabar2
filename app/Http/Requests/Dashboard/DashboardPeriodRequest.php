<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class DashboardPeriodRequest extends FormRequest
{
    /**
     * Custom ranges are capped at this many days to avoid an unbounded loop
     * (and payload) in DateRangeResolver/FinancialMetricsService::revenueTrend().
     */
    private const MAX_CUSTOM_RANGE_DAYS = 366;

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
            'to' => [
                'required_if:period,custom',
                'date',
                'after_or_equal:from',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->period() !== 'custom' || ! $this->fromDate()) {
                        return;
                    }

                    if (Carbon::parse($this->fromDate())->diffInDays(Carbon::parse($value)) > self::MAX_CUSTOM_RANGE_DAYS) {
                        $fail('The custom range cannot exceed '.self::MAX_CUSTOM_RANGE_DAYS.' days.');
                    }
                },
            ],
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

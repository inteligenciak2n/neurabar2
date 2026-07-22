<?php

namespace App\Http\Requests\Platform;

use App\Enums\BillingMode;
use App\Enums\SubscriptionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'billing_mode' => ['required', Rule::enum(BillingMode::class)],
            'status' => ['required', Rule::enum(SubscriptionStatus::class)],
            'billing_day' => ['required', 'integer', 'min:1', 'max:28'],
            'grace_period_days' => ['required', 'integer', 'min:0', 'max:30'],
            'started_at' => ['required', 'date'],
            'trial_ends_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
        ];
    }
}

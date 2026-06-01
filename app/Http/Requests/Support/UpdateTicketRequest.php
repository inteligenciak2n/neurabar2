<?php

namespace App\Http\Requests\Support;

use App\Enums\Support\TicketPriority;
use App\Enums\Support\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(TicketStatus::class)],
            'priority' => ['nullable', Rule::enum(TicketPriority::class)],
            'assigned_to' => ['nullable', 'uuid'],
            'category_id' => ['nullable', 'uuid'],
        ];
    }
}

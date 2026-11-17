<?php

namespace App\Http\Requests\Settings;

use App\Enums\PreferredPhoneHand;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-users') === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'preferred_phone_hand' => filled($this->input('preferred_phone_hand'))
                ? $this->input('preferred_phone_hand')
                : PreferredPhoneHand::default()->value,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', "unique:users,email,{$userId}"],
            'password' => ['nullable', Password::defaults()],
            'role' => ['required', new Enum(UserRole::class)],
            'pin' => ['nullable', 'string', 'max:10'],
            'preferred_phone_hand' => ['required', new Enum(PreferredPhoneHand::class)],
            'active' => ['boolean'],
        ];
    }
}

<?php

namespace App\Http\Requests\Settings;

use App\Enums\PreferredPhoneHand;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', new Enum(UserRole::class), function (string $attribute, mixed $value, \Closure $fail) {
                $role = UserRole::tryFrom($value);
                if ($role && ! in_array($role, UserRole::operationalRoles())) {
                    abort(403);
                }
            }],
            'pin' => ['nullable', 'string', 'max:10'],
            'preferred_phone_hand' => ['required', new Enum(PreferredPhoneHand::class)],
            'active' => ['boolean'],
        ];
    }
}

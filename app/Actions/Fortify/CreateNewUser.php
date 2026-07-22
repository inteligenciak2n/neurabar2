<?php

namespace App\Actions\Fortify;

use App\Enums\ProfileEnum;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $input['password'] ? $this->passwordRules() : 'nullable',
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'profile' => ProfileEnum::Client->value,
            'password' => Hash::make($this->resolvePassword($input['password'] ?? null)),
            'active' => true,
        ]);
    }

    private function resolvePassword(?string $password = null): string
    {
        return $password ?? bin2hex(random_bytes(16));
    }
}

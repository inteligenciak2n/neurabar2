<?php

namespace App\Actions\Fortify;

use App\Enums\ProfileEnum;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUserPlatform implements CreatesNewUsers
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
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();


        if(!isset($input['profile']) || !in_array($input['profile'], ProfileEnum::values())) {
            $input['profile'] = ProfileEnum::Client->value;
        }

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'profile' => $input['profile'],
            'password' => Hash::make($this->resolvePassword($input['password'] ?? null)),
            'active' => true,
        ]);

        if(isset($input['profile']) && in_array($input['profile'], ProfileEnum::operationalProfiles())) {
            $action = app(CreateUserOwnerDefinitions::class);
            $action->handle($user);
        }

        return $user;
    }

    private function resolvePassword(?string $password = null): string
    {
        return $password ?? bin2hex(random_bytes(16));
    }
}

<?php

namespace App\Actions\Fortify;

use App\Enums\ProfileEnum;
use App\Models\Tenant\PlanCatalog;
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
            'plan_catalog_id' => ['nullable', 'uuid', 'exists:plan_catalogs,id'],
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        if (! isset($input['profile']) || ! in_array($input['profile'], ProfileEnum::values())) {
            $input['profile'] = ProfileEnum::Client->value;
        }

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'profile' => $input['profile'],
            'password' => Hash::make($this->resolvePassword($input['password'] ?? null)),
            'active' => true,
        ]);

        if (isset($input['profile']) && in_array($input['profile'], ProfileEnum::operationalProfiles())) {
            $plan = isset($input['plan_catalog_id'])
                ? PlanCatalog::find($input['plan_catalog_id'])
                : null;

            $action = app(CreateUserOwnerDefinitions::class);
            $action->handle($user, $plan);
        }

        return $user;
    }

    private function resolvePassword(?string $password = null): string
    {
        return $password ?? bin2hex(random_bytes(16));
    }
}

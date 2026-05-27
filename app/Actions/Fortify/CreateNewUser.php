<?php

namespace App\Actions\Fortify;

use App\Models\Settings\KitchenStation;
use App\Models\Settings\PreparationStatus;
use App\Models\Settings\VenueSettings;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\Venue;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

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
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        return tap( User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make(  $this->resolvePassword($input['password']) ),
            'role' => $input['role'] ?? 'owner',
            'active' => $input['active'] ?? true,
        ]), function (User $user) use ($input) {

            $this->setUserDefaults($user);  

            if( isset($input['venue_id']) ) {
                $user->venue_id = $input['venue_id'];
                $user->save();
            }

            if( isset($input['corporation_id']) ) {
                $user->corporation_id = $input['corporation_id'];
                $user->save();
            }
        });
    }

    private function resolvePassword( ?string $password = null ): string
    {
        return $password ?? bin2hex(random_bytes(16));
    }

    private function setUserDefaults(User $user): void
    {
        if( $user->role->isOwnerOrAbove() ) {
            $action = app(CreateUserOwnerDefinitions::class);
            $action->handle($user);
        }
    }
}

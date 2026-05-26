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

            DB::beginTransaction();
            
            $plan = PlanCatalog::where('code', 'pro')->firstOrFail();
            
            $corporation = Corporation::create(
                [
                    'tax_id' => '00.000.000/0001-00',
                    'name' => 'Test Corp',
                    'email' => 'corp@test.com',
                    'contact_phone' => '11999990000',
                    'plan_catalog_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'subscription_value' => $plan->monthly_price,
                    'active' => true,
                ]
            );

            $venue = Venue::create(
                [
                    'call_waiter_slug' => 'test-bar',
                    'corporation_id' => $corporation->id,
                    'name' => 'Test Bar',
                    'tax_id' => '00.000.000/0001-00',
                    'phone' => '11999990000',
                    'city' => 'São Paulo',
                    'state' => 'SP',
                    'timezone' => 'America/Sao_Paulo',
                    'active' => true,
                ]
            );

            VenueSettings::create(
                [
                    'venue_id' => $venue->id,
                    'cover_charge' => 10.00,
                    'service_fee_percent' => 10.00,
                    'table_count' => 30,
                ]
            );


            $user->venue_id = $venue->id;
            $user->corporation_id = $corporation->id;
            $user->save();

            foreach (['Cozinha', 'Bar'] as $i => $stationName) {
                KitchenStation::create(
                    [
                        'venue_id' => $venue->id,
                        'name' => $stationName,
                        'sort_order' => $i + 1,
                        'active' => true,
                    ]
                );
            }

            $statuses = [
                ['name' => 'Pendente',     'color' => '#94a3b8', 'sort_order' => 1, 'show_to_customer' => false],
                ['name' => 'Em Preparo',   'color' => '#f59e0b', 'sort_order' => 2, 'show_to_customer' => false],
                ['name' => 'Pronto',       'color' => '#22c55e', 'sort_order' => 3, 'show_to_customer' => true],
            ];

            foreach ($statuses as $status) {
                PreparationStatus::create(
                    array_merge($status, ['venue_id' => $venue->id])
                );
            }

            DB::commit();
        }
    }
}

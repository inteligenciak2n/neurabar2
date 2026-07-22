<?php

namespace App\Actions\Onboarding;

use App\Enums\BillingMode;
use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\CorporationSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StartCorporationSubscriptionAction
{
    /**
     * Cria a corporation e a assinatura em trial do usuário, ativando os módulos
     * selecionados à la carte (sem vínculo com PlanCatalog).
     *
     * @param  array<int, string>  $moduleCodes
     */
    public function execute(User $user, array $moduleCodes, int $venueCount): Corporation
    {
        return DB::transaction(function () use ($user, $moduleCodes, $venueCount): Corporation {
            $corporation = Corporation::create([
                'owner_id' => $user->id,
                'name' => $user->name,
                'self_connection' => 'operation_default_1',
                'is_dedicated' => false,
                'active' => true,
            ]);

            $now = now();

            CorporationSubscription::create([
                'corporation_id' => $corporation->id,
                'billing_mode' => BillingMode::PerVenue->value,
                'status' => SubscriptionStatus::Trial->value,
                'started_at' => $now,
                'trial_ends_at' => $now->clone()->addDays((int) config('billing.trial_days')),
                'currency' => config('billing.currency', 'BRL'),
                'terms_accepted_at' => $now,
            ]);

            $codes = array_unique([ModuleCode::Menu->value, ...$moduleCodes]);

            foreach ($codes as $code) {
                CorporationModule::create([
                    'corporation_id' => $corporation->id,
                    'module_code' => $code,
                    'status' => ModuleStatus::Trial->value,
                    'started_at' => $now,
                ]);
            }

            session(['onboarding.venue_count' => max(1, $venueCount)]);

            return $corporation;
        });
    }
}

<?php

namespace Tests\Feature;

use App\Enums\ModuleCode;
use App\Models\Tenant\CorporationSubscription;
use App\Models\User;
use Database\Seeders\ModuleCatalogsSeeder;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshAllDatabases;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ModuleCatalogsSeeder::class);
    }

    private function verifiedUser(): User
    {
        return User::factory()->create([
            'current_venue_id' => null,
            'active' => true,
            'email_verified_at' => now(),
            'onboarding_completed_at' => null,
        ]);
    }

    public function test_subscription_step_can_be_rendered(): void
    {
        $user = $this->verifiedUser();

        $this->actingAs($user)
            ->get(route('onboarding.subscription.create'))
            ->assertOk();
    }

    public function test_subscription_step_creates_corporation_with_selected_modules(): void
    {
        $user = $this->verifiedUser();

        $response = $this->actingAs($user)->post(route('onboarding.subscription.store'), [
            'module_codes' => [ModuleCode::Kds->value],
            'venue_count' => 2,
            'terms' => true,
        ]);

        $response->assertRedirect(route('onboarding.corporation.create'));

        $user->refresh();
        $corporation = $user->ownedCorporation;
        $this->assertNotNull($corporation);

        $subscription = CorporationSubscription::where('corporation_id', $corporation->id)->first();
        $this->assertNotNull($subscription);
        $this->assertNull($subscription->plan_catalog_id);
        $this->assertNotNull($subscription->terms_accepted_at);

        $this->assertDatabaseHas('corporation_modules', [
            'corporation_id' => $corporation->id,
            'module_code' => ModuleCode::Menu->value,
        ]);
        $this->assertDatabaseHas('corporation_modules', [
            'corporation_id' => $corporation->id,
            'module_code' => ModuleCode::Kds->value,
        ]);

        $this->assertSame(2, session('onboarding.venue_count'));
    }

    public function test_corporation_step_redirects_back_when_subscription_not_started(): void
    {
        $user = $this->verifiedUser();

        $this->actingAs($user)
            ->get(route('onboarding.corporation.create'))
            ->assertRedirect(route('onboarding.subscription.create'));
    }

    public function test_corporation_step_finalizes_onboarding_with_real_and_skipped_venues(): void
    {
        $user = $this->verifiedUser();

        $this->actingAs($user)->post(route('onboarding.subscription.store'), [
            'module_codes' => [],
            'venue_count' => 2,
            'terms' => true,
        ])->assertRedirect(route('onboarding.corporation.create'));

        $corporation = $user->fresh()->ownedCorporation;
        $this->assertNotNull($corporation, 'corporation should have been created by subscription step');

        // Usa uma instância fresca do usuário para o próximo request: o guard de
        // autenticação do teste reaproveita o mesmo objeto entre chamadas na mesma
        // sessão de teste, e o Eloquent já teria cacheado `ownedCorporation` como
        // null (checado no primeiro passo antes de a corporation existir).
        $response = $this->actingAs($user->fresh())->post(route('onboarding.corporation.store'), [
            'name' => 'Minha Empresa Ltda',
            'tax_id' => '00.000.000/0001-00',
            'email' => 'contato@empresa.com',
            'contact_phone' => '11999990000',
            'venues' => [
                ['skip' => false, 'name' => 'Unidade Centro', 'timezone' => 'America/Sao_Paulo'],
                ['skip' => true],
            ],
        ]);

        $response->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertNotNull($user->onboarding_completed_at);
        $this->assertNotNull($user->current_venue_id);

        $corporation->refresh();
        $this->assertSame('Minha Empresa Ltda', $corporation->name);
        $this->assertSame(2, $corporation->venues()->count());

        $this->assertDatabaseHas('venues', ['name' => 'Unidade Centro']);
        $this->assertDatabaseHas('venues', ['name' => $user->name.' - Ponto de Venda 2']);

        $this->assertNull(session('onboarding.venue_count'));
    }
}

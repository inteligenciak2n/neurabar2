<?php

namespace Tests\Feature;

use App\Enums\ModuleCode;
use App\Models\Tenant\PlanCatalog;
use App\Models\User;
use Database\Seeders\ModuleCatalogsSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Jetstream;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshAllDatabases;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ModuleCatalogsSeeder::class);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_registration_screen_cannot_be_rendered_if_support_is_disabled(): void
    {
        if (Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is enabled.');
        }

        $response = $this->get('/register');

        $response->assertStatus(404);
    }

    public function test_new_users_can_register(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_new_users_can_register_with_selected_plan(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $this->seedPlanCatalogs();

        $plan = PlanCatalog::firstWhere('code', 'pro');
        $this->assertNotNull($plan);

        $response = $this->post('/register', [
            'name' => 'Test User With Plan',
            'email' => 'test-plan@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'plan_catalog_id' => $plan->id,
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::firstWhere('email', 'test-plan@example.com');
        $this->assertNotNull($user);

        $corporation = $user->ownedCorporation;
        $this->assertNotNull($corporation);

        $venue = $corporation->venues()->first();
        $this->assertNotNull($venue);

        foreach ($plan->includedModuleCodes() as $code) {
            $this->assertDatabaseHas('venue_modules', [
                'venue_id' => $venue->id,
                'module_code' => $code,
            ]);
        }
    }

    private function seedPlanCatalogs(): void
    {
        DB::connection('saas')->table('plan_catalogs')->insertOrIgnore([
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'code' => 'basic',
                'name' => 'Basic',
                'description' => 'Plano básico',
                'sort_order' => 1,
                'monthly_price' => 99.00,
                'included_modules' => json_encode([ModuleCode::Menu->value]),
                'active' => true,
                'plan_type' => 'shared',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'code' => 'pro',
                'name' => 'Pro',
                'description' => 'Plano pro',
                'sort_order' => 2,
                'monthly_price' => 199.00,
                'included_modules' => json_encode([
                    ModuleCode::Menu->value,
                    ModuleCode::Kds->value,
                ]),
                'active' => true,
                'plan_type' => 'shared',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

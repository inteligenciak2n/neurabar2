<?php

namespace Tests\Feature;

use App\Enums\AffiliateCodeStatus;
use App\Models\Tenant\AffiliateCode;
use App\Models\User;
use Database\Seeders\ModuleCatalogsSeeder;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Features;
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
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        // Como o email ainda não foi verificado, o acesso às rotas protegidas
        // deve ser redirecionado para a tela de verificação de email.
        $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));

        $user = User::firstWhere('email', 'test@example.com');
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->onboarding_completed_at);
        $this->assertNull($user->ownedCorporation);
    }

    public function test_registration_links_a_valid_affiliate_code(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $affiliate = AffiliateCode::create([
            'code' => 'NEURA2026',
            'name' => 'Parceiro Teste',
            'status' => AffiliateCodeStatus::Active->value,
        ]);

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'affiliate@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'affiliate_code' => 'neura2026',
        ]);

        $user = User::firstWhere('email', 'affiliate@example.com');
        $this->assertNotNull($user);
        $this->assertSame($affiliate->id, $user->affiliate_code_id);
    }

    public function test_registration_ignores_an_unknown_affiliate_code(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        Log::spy();

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'unknown-affiliate@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'affiliate_code' => 'INEXISTENTE',
        ]);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => $context['affiliate_code'] === 'INEXISTENTE');

        $this->assertAuthenticated();

        $user = User::firstWhere('email', 'unknown-affiliate@example.com');
        $this->assertNotNull($user);
        $this->assertNull($user->affiliate_code_id);
    }
}

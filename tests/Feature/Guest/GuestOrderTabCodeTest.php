<?php

namespace Tests\Feature\Guest;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Models\GuestSession;
use App\Models\Menu\Category;
use App\Models\Menu\Menu;
use App\Models\Menu\Product;
use App\Models\Orders\Attendance;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use App\Services\GuestTokenService;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class GuestOrderTabCodeTest extends TestCase
{
    use RefreshAllDatabases;

    private Venue $venue;

    private string $token;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->venue = Venue::factory()->create(['active' => true]);
        $this->token = rtrim(base64_encode(json_encode(['v' => $this->venue->id])), '=');

        CorporationModule::firstOrCreate(
            ['corporation_id' => $this->venue->corporation_id, 'module_code' => ModuleCode::SelfOrder->value],
            ['status' => ModuleStatus::Active, 'started_at' => now()]
        );
        VenueModule::factory()->create([
            'venue_id' => $this->venue->id,
            'module_code' => ModuleCode::SelfOrder->value,
            'status' => ModuleStatus::Active,
        ]);

        $menu = Menu::factory()->create(['venue_id' => $this->venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $this->product = Product::factory()->create(['category_id' => $category->id, 'price' => 10, 'active' => true]);
    }

    private function openGuestSession(): GuestSession
    {
        return app(GuestTokenService::class)->createSession($this->venue, null, null, '1234');
    }

    private function placeOrder(GuestSession $session, array $extra = [])
    {
        return $this->withCredentials()
            ->withUnencryptedCookie('guest_token', $session->guest_token)
            ->postJson("/g/{$this->token}/orders", array_merge([
                'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
            ], $extra));
    }

    public function test_order_with_tab_code_opens_an_attendance_carrying_the_normalized_code(): void
    {
        $session = $this->openGuestSession();

        $this->placeOrder($session, ['tab_code' => ' ab-123 '])
            ->assertCreated()
            ->assertJsonPath('tab_code', 'AB-123');

        $this->assertDatabaseHas('attendances', [
            'venue_id' => $this->venue->id,
            'code' => 'AB-123',
            'status' => 'open',
        ]);
    }

    public function test_order_attaches_to_the_open_attendance_holding_the_tab_card(): void
    {
        $existing = Attendance::factory()->open()->create([
            'venue_id' => $this->venue->id,
            'code' => '777',
        ]);
        $session = $this->openGuestSession();

        $response = $this->placeOrder($session, ['tab_code' => '777'])->assertCreated();

        $this->assertSame(1, Attendance::withoutGlobalScopes()->where('code', '777')->count());
        $this->assertDatabaseHas('orders', ['id' => $response->json('order_id'), 'attendance_id' => $existing->id]);
    }

    public function test_second_order_keeps_the_same_attendance_and_ignores_a_different_code(): void
    {
        $session = $this->openGuestSession();

        $this->placeOrder($session, ['tab_code' => '100'])->assertCreated();
        $this->placeOrder($session, ['tab_code' => '200'])
            ->assertCreated()
            ->assertJsonPath('tab_code', '100');

        $this->assertSame(1, Attendance::withoutGlobalScopes()->where('venue_id', $this->venue->id)->count());
    }

    public function test_tab_card_held_by_another_live_session_is_rejected(): void
    {
        $first = $this->openGuestSession();
        $this->placeOrder($first, ['tab_code' => '555'])->assertCreated();

        $second = $this->openGuestSession();

        $this->placeOrder($second, ['tab_code' => '555'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tab_code');
    }

    public function test_tab_card_of_a_closed_attendance_can_be_reused(): void
    {
        Attendance::factory()->closed()->create(['venue_id' => $this->venue->id, 'code' => '909']);
        $session = $this->openGuestSession();

        $this->placeOrder($session, ['tab_code' => '909'])->assertCreated();

        $this->assertSame(1, Attendance::withoutGlobalScopes()->open()->where('code', '909')->count());
    }

    public function test_tab_code_with_invalid_characters_is_rejected(): void
    {
        $session = $this->openGuestSession();

        $this->placeOrder($session, ['tab_code' => 'a b;--'])->assertJsonValidationErrors('tab_code');
    }
}

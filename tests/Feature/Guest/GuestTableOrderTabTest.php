<?php

namespace Tests\Feature\Guest;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Models\GuestSession;
use App\Models\Menu\Category;
use App\Models\Menu\Menu;
use App\Models\Menu\Product;
use App\Models\Orders\Attendance;
use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\ServiceLocation;
use App\Models\Settings\Tab;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use App\Services\GuestTokenService;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class GuestTableOrderTabTest extends TestCase
{
    use RefreshAllDatabases;

    private Venue $venue;

    private ServiceLocation $location;

    private string $token;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->venue = Venue::factory()->create(['active' => true]);
        $channel = AttendanceChannel::factory()->create([
            'venue_id' => $this->venue->id,
            'uses_service_location' => true,
            'requires_tab' => true,
        ]);
        $this->location = ServiceLocation::factory()->create([
            'venue_id' => $this->venue->id,
            'name' => 'Mesa 4',
            'default_attendance_channel_id' => $channel->id,
        ]);
        $this->token = rtrim(base64_encode(json_encode([
            'v' => $this->venue->id,
            'l' => $this->location->id,
            'c' => $channel->id,
        ])), '=');

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
        $decoded = app(GuestTokenService::class)->decode($this->token);

        return app(GuestTokenService::class)->createSession(
            $this->venue,
            $decoded['serviceLocation'],
            $decoded['attendanceChannel'],
        );
    }

    private function placeOrder(GuestSession $session, array $extra = [])
    {
        return $this->withCredentials()
            ->withUnencryptedCookie('guest_token', $session->guest_token)
            ->postJson("/g/{$this->token}/orders", array_merge([
                'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
            ], $extra));
    }

    public function test_order_on_a_table_with_one_open_tab_attaches_silently(): void
    {
        $tab = Tab::factory()->create(['venue_id' => $this->venue->id, 'code' => '3']);
        $existing = Attendance::factory()->open()->create([
            'venue_id' => $this->venue->id,
            'service_location_id' => $this->location->id,
            'tab_id' => $tab->id,
        ]);
        $session = $this->openGuestSession();

        $this->placeOrder($session)
            ->assertCreated()
            ->assertJsonPath('tab_code', '3')
            ->assertJsonPath('needs_tab_link', false);

        $this->assertDatabaseHas('orders', [
            'attendance_id' => $existing->id,
        ]);
        $this->assertSame(1, Attendance::withoutGlobalScopes()->where('service_location_id', $this->location->id)->count());
    }

    public function test_order_on_a_table_with_no_tab_stays_unlinked_for_the_waiter(): void
    {
        $session = $this->openGuestSession();

        $this->placeOrder($session)
            ->assertCreated()
            ->assertJsonPath('needs_tab_link', true);

        $this->assertDatabaseHas('attendances', [
            'venue_id' => $this->venue->id,
            'service_location_id' => $this->location->id,
            'tab_id' => null,
            'code' => null,
            'status' => 'open',
        ]);
    }

    public function test_order_on_a_table_with_several_tabs_asks_which_tab(): void
    {
        $tabThree = Tab::factory()->create(['venue_id' => $this->venue->id, 'code' => '3']);
        $tabFive = Tab::factory()->create(['venue_id' => $this->venue->id, 'code' => '5']);
        $comandaThree = Attendance::factory()->open()->create([
            'venue_id' => $this->venue->id,
            'service_location_id' => $this->location->id,
            'tab_id' => $tabThree->id,
        ]);
        Attendance::factory()->open()->create([
            'venue_id' => $this->venue->id,
            'service_location_id' => $this->location->id,
            'tab_id' => $tabFive->id,
        ]);
        $session = $this->openGuestSession();

        $this->placeOrder($session)
            ->assertConflict()
            ->assertJsonPath('needs_tab_choice', true)
            ->assertJsonPath('message', 'Which tab?')
            ->assertJsonCount(2, 'tabs');

        $this->assertDatabaseMissing('orders', ['attendance_id' => $comandaThree->id]);
    }

    public function test_guest_can_attach_the_order_to_a_tab_from_the_table(): void
    {
        $tabThree = Tab::factory()->create(['venue_id' => $this->venue->id, 'code' => '3']);
        $tabFive = Tab::factory()->create(['venue_id' => $this->venue->id, 'code' => '5']);
        $comandaThree = Attendance::factory()->open()->create([
            'venue_id' => $this->venue->id,
            'service_location_id' => $this->location->id,
            'tab_id' => $tabThree->id,
        ]);
        Attendance::factory()->open()->create([
            'venue_id' => $this->venue->id,
            'service_location_id' => $this->location->id,
            'tab_id' => $tabFive->id,
        ]);
        $session = $this->openGuestSession();

        $this->placeOrder($session, ['attendance_id' => $comandaThree->id])
            ->assertCreated()
            ->assertJsonPath('tab_code', '3');

        $this->assertDatabaseHas('orders', ['attendance_id' => $comandaThree->id]);
    }

    public function test_guest_can_leave_the_tab_for_the_waiter_to_define(): void
    {
        Tab::factory()->create(['venue_id' => $this->venue->id, 'code' => '3']);
        Tab::factory()->create(['venue_id' => $this->venue->id, 'code' => '5']);
        Attendance::factory()->open()->create([
            'venue_id' => $this->venue->id,
            'service_location_id' => $this->location->id,
            'tab_id' => Tab::withoutGlobalScopes()->where('code', '3')->first()->id,
        ]);
        Attendance::factory()->open()->create([
            'venue_id' => $this->venue->id,
            'service_location_id' => $this->location->id,
            'tab_id' => Tab::withoutGlobalScopes()->where('code', '5')->first()->id,
        ]);
        $session = $this->openGuestSession();

        $this->placeOrder($session, ['waiter_defines' => true])
            ->assertCreated()
            ->assertJsonPath('needs_tab_link', true);

        $this->assertDatabaseHas('attendances', [
            'venue_id' => $this->venue->id,
            'service_location_id' => $this->location->id,
            'tab_id' => null,
            'status' => 'open',
        ]);
    }

    public function test_table_order_ignores_a_typed_tab_code_when_one_tab_is_open(): void
    {
        $tab = Tab::factory()->create(['venue_id' => $this->venue->id, 'code' => '3']);
        $existing = Attendance::factory()->open()->create([
            'venue_id' => $this->venue->id,
            'service_location_id' => $this->location->id,
            'tab_id' => $tab->id,
        ]);
        $session = $this->openGuestSession();

        $this->placeOrder($session, ['tab_code' => '999'])
            ->assertCreated()
            ->assertJsonPath('tab_code', '3');

        $this->assertDatabaseHas('orders', ['attendance_id' => $existing->id]);
        $this->assertDatabaseMissing('attendances', ['code' => '999']);
    }
}

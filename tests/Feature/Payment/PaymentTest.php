<?php

namespace Tests\Feature\Payment;

use App\Enums\UserRole;
use App\Models\Menu\Category;
use App\Models\Menu\Menu;
use App\Models\Menu\Product;
use App\Models\Orders\Attendance;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Payment\Payment;
use App\Models\Settings\VenueSettings;
use App\Models\Tenant\Venue;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function createAttendanceWithItems(Venue $venue, float $itemPrice = 50.00, int $partySize = 2): Attendance
    {
        VenueSettings::factory()->create([
            'venue_id' => $venue->id,
            'cover_charge' => 10.00,
            'service_fee_percent' => 10.00,
        ]);

        $attendance = Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'party_size' => $partySize,
        ]);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => $itemPrice]);
        $order = Order::factory()->create(['attendance_id' => $attendance->id]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $itemPrice,
        ]);

        return $attendance;
    }

    public function test_calculate_total_applies_cover_charge_and_service_fee(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $attendance = $this->createAttendanceWithItems($venue, 100.00, 2);

        /** @var PaymentService $service */
        $service = app(PaymentService::class);
        $totals = $service->calculateTotal($attendance);

        // items_total = 100, cover = 10*2 = 20, subtotal = 120, fee = 12, grand = 132
        $this->assertEquals(100.00, $totals['items_total']);
        $this->assertEquals(20.00, $totals['cover_charge_total']);
        $this->assertEquals(12.00, $totals['service_fee_total']);
        $this->assertEquals(132.00, $totals['grand_total']);
    }

    public function test_cover_charge_is_zero_when_party_size_is_null(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        VenueSettings::factory()->create([
            'venue_id' => $venue->id,
            'cover_charge' => 10.00,
            'service_fee_percent' => 10.00,
        ]);

        $attendance = Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'party_size' => null,
        ]);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 100.00]);
        $order = Order::factory()->create(['attendance_id' => $attendance->id]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'unit_price' => 100.00,
            'quantity' => 1,
        ]);

        /** @var PaymentService $service */
        $service = app(PaymentService::class);
        $totals = $service->calculateTotal($attendance);

        $this->assertEquals(0.00, $totals['cover_charge_total']);
    }

    public function test_split_total_rounds_to_two_decimal_places(): void
    {
        /** @var PaymentService $service */
        $service = app(PaymentService::class);

        $result = $service->splitTotal(100.00, 3);
        $this->assertEquals(33.33, $result);
    }

    public function test_register_payment_creates_payment_and_closes_attendance(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $attendance = $this->createAttendanceWithItems($venue, 100.00, 2);

        // grand_total = 132.00
        $this->post(route('payment.store', $attendance->id), [
            'party_size' => 2,
            'methods' => [
                ['type' => 'cash', 'amount' => 132.00],
            ],
        ])->assertRedirect(route('attendances.index'));

        $this->assertDatabaseHas('payments', ['attendance_id' => $attendance->id]);
        $this->assertDatabaseHas('payment_items', ['method' => 'cash', 'amount' => 132.00]);
        $this->assertDatabaseHas('attendances', ['id' => $attendance->id, 'status' => 'closed']);
    }

    public function test_methods_sum_mismatch_returns_validation_error(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $attendance = $this->createAttendanceWithItems($venue, 100.00, 2);

        // grand_total = 132, but we send 100
        $this->post(route('payment.store', $attendance->id), [
            'party_size' => 2,
            'methods' => [
                ['type' => 'cash', 'amount' => 100.00],
            ],
        ])->assertSessionHasErrors('methods');
    }

    public function test_payment_for_closed_attendance_returns_validation_error(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        VenueSettings::factory()->create(['venue_id' => $venue->id]);
        $attendance = Attendance::factory()->closed()->create(['venue_id' => $venue->id]);

        $this->post(route('payment.store', $attendance->id), [
            'methods' => [['type' => 'cash', 'amount' => 10.00]],
        ])->assertSessionHasErrors('attendance');
    }

    public function test_duplicate_payment_returns_validation_error(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $attendance = $this->createAttendanceWithItems($venue, 100.00, 2);
        Payment::factory()->create(['attendance_id' => $attendance->id]);

        $this->post(route('payment.store', $attendance->id), [
            'party_size' => 2,
            'methods' => [['type' => 'cash', 'amount' => 132.00]],
        ])->assertSessionHasErrors('attendance');
    }

    public function test_payment_show_page_renders_with_totals(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $attendance = $this->createAttendanceWithItems($venue, 50.00, 1);

        $this->get(route('payment.show', $attendance->id))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page->component('Payment/Index')
                    ->has('totals')
                    ->has('paymentMethods')
            );
    }
}

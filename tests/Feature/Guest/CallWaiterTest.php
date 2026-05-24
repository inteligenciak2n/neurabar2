<?php

namespace Tests\Feature\Guest;

use App\Events\Orders\OrderPlaced;
use App\Models\Tenant\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CallWaiterTest extends TestCase
{
    use RefreshDatabase;

    private function createVenueWithSlug(string $slug, ?string $passphrase = null): Venue
    {
        return Venue::factory()->create([
            'call_waiter_slug' => $slug,
            'call_waiter_passphrase' => $passphrase,
        ]);
    }

    public function test_show_returns_call_waiter_page_for_valid_slug(): void
    {
        $this->createVenueWithSlug('test-bar');

        $this->get(route('call-waiter.show', 'test-bar'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page->component('Guest/CallWaiter')
                    ->where('passphraseRequired', false)
            );
    }

    public function test_show_returns_404_for_invalid_slug(): void
    {
        $this->get(route('call-waiter.show', 'nonexistent-slug'))
            ->assertNotFound();
    }

    public function test_valid_request_creates_attendance_order_and_item(): void
    {
        Event::fake([OrderPlaced::class]);

        $this->createVenueWithSlug('test-bar');

        $this->post(route('call-waiter.store', 'test-bar'), [
            'message' => 'Need more napkins',
            'customer_identifier' => 'Table 5',
        ])->assertStatus(201)->assertJsonStructure(['protocol']);

        $this->assertDatabaseHas('attendances', ['channel' => 'service_request']);
        $this->assertDatabaseHas('orders', ['order_number' => 1]);
        $this->assertDatabaseHas('order_items', ['notes' => 'Need more napkins', 'product_id' => null]);

        Event::assertDispatched(OrderPlaced::class);
    }

    public function test_invalid_passphrase_returns_validation_error(): void
    {
        Event::fake([OrderPlaced::class]);

        $this->createVenueWithSlug('secure-bar', 'secret123');

        $this->post(route('call-waiter.store', 'secure-bar'), [
            'message' => 'Help please',
            'passphrase' => 'wrongpassphrase',
        ])->assertSessionHasErrors('passphrase');

        Event::assertNotDispatched(OrderPlaced::class);
    }

    public function test_valid_passphrase_succeeds(): void
    {
        Event::fake([OrderPlaced::class]);

        $this->createVenueWithSlug('secure-bar', 'secret123');

        $this->post(route('call-waiter.store', 'secure-bar'), [
            'message' => 'Help please',
            'passphrase' => 'secret123',
        ])->assertStatus(201);

        Event::assertDispatched(OrderPlaced::class);
    }

    public function test_empty_message_returns_validation_error(): void
    {
        $this->createVenueWithSlug('test-bar');

        $this->post(route('call-waiter.store', 'test-bar'), [
            'message' => '',
        ])->assertSessionHasErrors('message');
    }

    public function test_store_returns_404_for_invalid_slug(): void
    {
        $this->post(route('call-waiter.store', 'nonexistent'), [
            'message' => 'Help',
        ])->assertNotFound();
    }

    public function test_passphrase_required_flag_shows_in_show_response(): void
    {
        $this->createVenueWithSlug('locked-bar', 'mypassword');

        $this->get(route('call-waiter.show', 'locked-bar'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page->where('passphraseRequired', true)
            );
    }
}

<?php

namespace Tests\Feature\Billing\Listeners;

use App\Enums\ModuleCode;
use App\Events\Orders\GuestSignaled;
use App\Listeners\Billing\RecordSignalUsage;
use App\Models\Tenant\Venue;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class RecordSignalUsageTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_records_signal_usage_for_direct_waiter_and_voice_command(): void
    {
        $venue = Venue::factory()->create();
        $this->ensureVenueHasSubscriptionAndMenu($venue);

        (new RecordSignalUsage)->handle(new GuestSignaled(
            venueId: (string) $venue->id,
            locationName: 'Mesa 1',
            message: 'Preciso de ajuda',
            signalOnly: true,
        ));

        foreach ([ModuleCode::DirectWaiter, ModuleCode::VoiceCommand] as $code) {
            $this->assertDatabaseHas('venue_usage_records', [
                'venue_id' => $venue->id,
                'module_code' => $code->value,
                'period' => now()->format('Y-m'),
                'quantity' => 1,
            ]);
        }
    }

    public function test_does_not_record_when_venue_id_is_empty(): void
    {
        (new RecordSignalUsage)->handle(new GuestSignaled(
            venueId: '',
            locationName: 'Mesa 1',
            message: 'Preciso de ajuda',
            signalOnly: true,
        ));

        $this->assertDatabaseCount('venue_usage_records', 0);
    }
}

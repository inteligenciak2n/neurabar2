<?php

namespace Tests\Feature\Settings;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\UserRole;
use App\Models\Settings\VenueSettings;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class WaiterCallPhraseTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_unconfigured_venue_starts_with_suggested_deletable_phrases(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->get(route('settings.waiter-call-phrases.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/WaiterCallPhrases')
                ->where('phrases', VenueSettings::SUGGESTED_WAITER_CALL_PHRASES)
            );

        $this->get(route('menu.preview.customer'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('waiterCallPhrases', VenueSettings::SUGGESTED_WAITER_CALL_PHRASES)
            );
    }

    public function test_owner_can_delete_a_suggested_phrase(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $remaining = VenueSettings::SUGGESTED_WAITER_CALL_PHRASES;
        unset($remaining[1]);
        $remaining = array_values($remaining);

        $this->put(route('settings.waiter-call-phrases.update'), [
            'phrases' => $remaining,
        ])->assertRedirect();

        $this->get(route('settings.waiter-call-phrases.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('phrases', $remaining)
            );

        $this->assertSame(
            $remaining,
            VenueSettings::withoutGlobalScopes()->where('venue_id', $venue->id)->first()?->waiter_call_phrases,
        );
    }

    public function test_clearing_all_phrases_does_not_restore_suggestions(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->put(route('settings.waiter-call-phrases.update'), [
            'phrases' => [],
        ])->assertRedirect();

        $this->get(route('settings.waiter-call-phrases.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('phrases', [])
            );
    }

    public function test_owner_can_view_waiter_call_phrases(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        VenueSettings::factory()->create([
            'venue_id' => $venue->id,
            'waiter_call_phrases' => ['Molho extra'],
        ]);

        $this->get(route('settings.waiter-call-phrases.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/WaiterCallPhrases')
                ->where('phrases', ['Molho extra'])
            );
    }

    public function test_owner_can_save_custom_waiter_call_phrases(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->put(route('settings.waiter-call-phrases.update'), [
            'phrases' => ['  Molho extra  ', 'Água com gás', 'molho extra'],
        ])->assertRedirect();

        $settings = VenueSettings::withoutGlobalScopes()->where('venue_id', $venue->id)->first();

        $this->assertNotNull($settings);
        $this->assertSame(['Molho extra', 'Água com gás'], $settings->waiter_call_phrases);
    }

    public function test_phrases_cannot_exceed_the_limit(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->put(route('settings.waiter-call-phrases.update'), [
            'phrases' => array_map(fn (int $index) => "Frase {$index}", range(1, 9)),
        ])->assertSessionHasErrors('phrases');
    }

    public function test_owner_can_save_up_to_eight_phrases_and_delete_any_of_them(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);
        $phrases = array_map(fn (int $index) => "Frase {$index}", range(1, 8));

        $this->put(route('settings.waiter-call-phrases.update'), [
            'phrases' => $phrases,
        ])->assertRedirect();

        $settings = VenueSettings::withoutGlobalScopes()->where('venue_id', $venue->id)->first();
        $this->assertSame($phrases, $settings->waiter_call_phrases);

        array_splice($phrases, 2, 1);

        $this->put(route('settings.waiter-call-phrases.update'), [
            'phrases' => $phrases,
        ])->assertRedirect();

        $settings->refresh();
        $this->assertSame($phrases, $settings->waiter_call_phrases);
        $this->assertCount(7, $settings->waiter_call_phrases);
    }

    public function test_phrase_text_is_required_and_capped(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->put(route('settings.waiter-call-phrases.update'), [
            'phrases' => [''],
        ])->assertSessionHasErrors('phrases.0');

        $this->put(route('settings.waiter-call-phrases.update'), [
            'phrases' => [str_repeat('a', 61)],
        ])->assertSessionHasErrors('phrases.0');
    }

    public function test_attendant_cannot_manage_waiter_call_phrases(): void
    {
        $this->loginAs(UserRole::Attendant);

        $this->get(route('settings.waiter-call-phrases.index'))->assertForbidden();
        $this->put(route('settings.waiter-call-phrases.update'), [
            'phrases' => ['Molho extra'],
        ])->assertForbidden();
    }

    public function test_customer_preview_includes_custom_waiter_call_phrases(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        VenueSettings::factory()->create([
            'venue_id' => $venue->id,
            'waiter_call_phrases' => ['Molho extra'],
        ]);

        $this->get(route('menu.preview.customer'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guest/Menu')
                ->where('preview', true)
                ->where('waiterCallPhrases', ['Molho extra'])
            );
    }

    public function test_public_menu_includes_custom_waiter_call_phrases_when_direct_waiter_is_active(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        CorporationModule::factory()->create([
            'corporation_id' => $venue->corporation_id,
            'module_code' => ModuleCode::DirectWaiter->value,
            'status' => ModuleStatus::Active,
        ]);
        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::DirectWaiter->value,
            'status' => ModuleStatus::Active,
        ]);
        VenueSettings::factory()->create([
            'venue_id' => $venue->id,
            'waiter_call_phrases' => ['Molho extra'],
        ]);

        $token = rtrim(base64_encode(json_encode(['v' => $venue->id])), '=');

        $this->get("/g/{$token}/menu")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guest/Menu')
                ->where('hasDirectWaiter', true)
                ->where('waiterCallPhrases', ['Molho extra'])
            );
    }
}

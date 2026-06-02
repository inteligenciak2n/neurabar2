<?php

namespace Tests\Feature\Settings;

use App\Enums\UserRole;
use App\Mail\VenueInvitationMail;
use App\Models\Tenant\Venue;
use App\Models\User;
use App\Models\VenueInvitation;
use Illuminate\Support\Facades\Mail;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class InviteUserTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_creating_user_with_existing_email_sends_invitation(): void
    {
        Mail::fake();

        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        User::factory()->create(['email' => 'existing@test.com']);

        $this->post(route('settings.users.store'), [
            'name' => 'Ignored',
            'email' => 'existing@test.com',
            'password' => 'password',
            'role' => 'attendant',
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('venue_invitations', [
            'email' => 'existing@test.com',
            'venue_id' => $venue->id,
            'role' => 'attendant',
        ]);

        Mail::assertSent(VenueInvitationMail::class, fn ($mail) => $mail->hasTo('existing@test.com'));
    }

    public function test_invitation_can_be_accepted(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $invitation = VenueInvitation::factory()->create([
            'venue_id' => $venue->id,
            'email' => 'invited@test.com',
            'role' => UserRole::Attendant,
            'expires_at' => now()->addHours(72),
            'accepted_at' => null,
        ]);

        $user = User::factory()->create(['email' => 'invited@test.com']);

        $this->actingAs($user)
            ->post(route('invitations.accept', $invitation->token))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('user_venue', [
            'user_id' => $user->id,
            'venue_id' => $venue->id,
            'role' => 'attendant',
        ]);

        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_expired_invitation_cannot_be_accepted(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $invitation = VenueInvitation::factory()->create([
            'venue_id' => $venue->id,
            'email' => 'invited@test.com',
            'role' => UserRole::Attendant,
            'expires_at' => now()->subHour(),
            'accepted_at' => null,
        ]);

        $user = User::factory()->create(['email' => 'invited@test.com']);

        $this->actingAs($user)
            ->post(route('invitations.accept', $invitation->token))
            ->assertSessionHasErrors('token');
    }

    public function test_invitation_email_mismatch_returns_error(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $invitation = VenueInvitation::factory()->create([
            'venue_id' => $venue->id,
            'email' => 'someone@test.com',
            'role' => UserRole::Attendant,
            'expires_at' => now()->addHours(72),
            'accepted_at' => null,
        ]);

        $user = User::factory()->create(['email' => 'other@test.com']);

        $this->actingAs($user)
            ->post(route('invitations.accept', $invitation->token))
            ->assertSessionHasErrors('token');
    }
}

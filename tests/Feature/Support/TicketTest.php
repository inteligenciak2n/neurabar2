<?php

namespace Tests\Feature\Support;

use App\Enums\Support\TicketStatus;
use App\Enums\UserRole;
use App\Models\Support\Ticket;
use App\Models\Support\TicketCategory;
use App\Models\Support\TicketMessage;
use App\Models\Support\TicketRead;
use App\Models\Tenant\Venue;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshAllDatabases;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function makeAuthUser(): User
    {
        $venue = Venue::factory()->create(['active' => true]);
        $user = $this->loginAs(UserRole::Attendant, $venue);

        return $user;
    }

    public function test_authenticated_user_can_view_support_dashboard(): void
    {
        $this->makeAuthUser();

        $this->get(route('support.dashboard'))->assertOk();
    }

    public function test_authenticated_user_can_view_ticket_list(): void
    {
        $this->makeAuthUser();

        $this->get(route('support.tickets.index'))->assertOk();
    }

    public function test_authenticated_user_can_create_ticket_form(): void
    {
        $this->makeAuthUser();

        $this->get(route('support.tickets.create'))->assertOk();
    }

    public function test_user_can_open_a_ticket(): void
    {
        Notification::fake();
        Storage::fake('local');

        $user = $this->makeAuthUser();
        $category = TicketCategory::create([
            'name' => 'Geral',
            'active' => true,
        ]);

        $response = $this->post(route('support.tickets.store'), [
            'category_id' => $category->id,
            'subject' => 'Test ticket subject',
            'body' => 'Description of the issue',
        ]);

        $response->assertRedirect();
        $this->assertEquals(1, Ticket::on('saas')->where('user_id', $user->id)->count());
        $this->assertEquals(1, TicketMessage::on('saas')->count());
    }

    public function test_user_can_open_ticket_with_attachment(): void
    {
        Notification::fake();
        Storage::fake('local');

        $user = $this->makeAuthUser();
        $category = TicketCategory::create(['name' => 'Billing', 'active' => true]);

        $this->post(route('support.tickets.store'), [
            'category_id' => $category->id,
            'subject' => 'Attachment test',
            'body' => 'Body text',
            'attachments' => [
                UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ],
        ])->assertRedirect();

        $ticket = Ticket::on('saas')->where('user_id', $user->id)->first();
        $this->assertEquals(1, $ticket->messages->first()->attachments->count());
    }

    public function test_user_can_reply_to_own_ticket(): void
    {
        Notification::fake();
        Storage::fake('local');

        $user = $this->makeAuthUser();
        $category = TicketCategory::create(['name' => 'Geral', 'active' => true]);

        $this->post(route('support.tickets.store'), [
            'category_id' => $category->id,
            'subject' => 'My ticket',
            'body' => 'First message',
        ]);

        $ticket = Ticket::on('saas')->where('user_id', $user->id)->first();

        $this->post(route('support.tickets.messages.store', $ticket->id), [
            'body' => 'Follow-up reply',
        ])->assertRedirect();

        $this->assertEquals(2, $ticket->messages()->count());
    }

    public function test_user_cannot_reply_to_another_users_ticket(): void
    {
        Notification::fake();

        $otherUser = User::factory()->create(['active' => true]);
        $category = TicketCategory::create(['name' => 'Geral', 'active' => true]);
        $ticket = Ticket::on('saas')->create([
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
            'subject' => 'Other ticket',
            'status' => TicketStatus::Open->value,
            'priority' => 'medium',
        ]);

        $this->makeAuthUser();

        $this->post(route('support.tickets.messages.store', $ticket->id), [
            'body' => 'Trying to reply',
        ])->assertForbidden();
    }

    public function test_user_can_close_own_ticket(): void
    {
        Notification::fake();

        $user = $this->makeAuthUser();
        $category = TicketCategory::create(['name' => 'Geral', 'active' => true]);

        $ticket = Ticket::on('saas')->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'subject' => 'Close test',
            'status' => TicketStatus::Open->value,
            'priority' => 'medium',
        ]);

        $this->post(route('support.tickets.close', $ticket->id))->assertRedirect();

        $this->assertEquals(TicketStatus::Closed->value, $ticket->fresh()->status->value);
    }

    public function test_user_can_rate_resolved_ticket(): void
    {
        $user = $this->makeAuthUser();
        $category = TicketCategory::create(['name' => 'Geral', 'active' => true]);

        $ticket = Ticket::on('saas')->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'subject' => 'Rating test',
            'status' => TicketStatus::Resolved->value,
            'priority' => 'medium',
        ]);

        $this->post(route('support.tickets.rate', $ticket->id), [
            'score' => 4,
            'comment' => 'Good support!',
        ])->assertRedirect();

        $this->assertNotNull($ticket->fresh()->rating);
        $this->assertEquals(4, $ticket->fresh()->rating->score);
    }

    public function test_user_cannot_rate_open_ticket(): void
    {
        $user = $this->makeAuthUser();
        $category = TicketCategory::create(['name' => 'Geral', 'active' => true]);

        $ticket = Ticket::on('saas')->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'subject' => 'Rating test open',
            'status' => TicketStatus::Open->value,
            'priority' => 'medium',
        ]);

        $this->post(route('support.tickets.rate', $ticket->id), [
            'score' => 5,
        ])->assertSessionHasErrors('ticket');
    }

    public function test_ticket_has_no_unread_messages_initially_for_client(): void
    {
        $user = $this->makeAuthUser();
        $category = TicketCategory::create(['name' => 'Geral', 'active' => true]);

        $this->post(route('support.tickets.store'), [
            'category_id' => $category->id,
            'subject' => 'Unread test',
            'body' => 'Initial message',
        ]);

        $ticket = Ticket::on('saas')->where('user_id', $user->id)->first();

        $response = $this->get(route('support.tickets.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Support/Tickets/Index')
            ->where('tickets.data.0.unread_count', 0)
        );
    }

    public function test_opening_ticket_marks_it_as_read_for_client(): void
    {
        Notification::fake();

        $user = $this->makeAuthUser();
        $category = TicketCategory::create(['name' => 'Geral', 'active' => true]);

        $ticket = Ticket::on('saas')->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'subject' => 'Mark read test',
            'status' => 'open',
            'priority' => 'medium',
        ]);

        // Simulate an agent message (platform_user author_type)
        TicketMessage::on('saas')->create([
            'ticket_id' => $ticket->id,
            'author_id' => (string) \Str::uuid(),
            'author_type' => 'platform_user',
            'body' => 'Agent reply',
        ]);

        // Before opening, no read record exists → unread_count = 1
        $this->assertNull(TicketRead::where('ticket_id', $ticket->id)->first());

        // Opening the ticket marks it as read
        $this->get(route('support.tickets.show', $ticket->id))->assertOk();

        $this->assertNotNull(TicketRead::where('ticket_id', $ticket->id)->where('reader_id', $user->id)->first());
    }

    public function test_replying_to_ticket_marks_it_as_read_for_client(): void
    {
        Notification::fake();

        $user = $this->makeAuthUser();
        $category = TicketCategory::create(['name' => 'Geral', 'active' => true]);

        $ticket = Ticket::on('saas')->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'subject' => 'Reply marks read test',
            'status' => 'open',
            'priority' => 'medium',
        ]);

        $this->post(route('support.tickets.messages.store', $ticket->id), [
            'body' => 'Client reply',
        ])->assertRedirect();

        $this->assertNotNull(
            TicketRead::where('ticket_id', $ticket->id)->where('reader_id', $user->id)->first()
        );
    }
}

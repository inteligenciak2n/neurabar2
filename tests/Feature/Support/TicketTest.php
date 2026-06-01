<?php

namespace Tests\Feature\Support;

use App\Enums\Support\TicketStatus;
use App\Enums\UserRole;
use App\Models\Support\Ticket;
use App\Models\Support\TicketCategory;
use App\Models\Support\TicketMessage;
use App\Models\Tenant\Venue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    protected static bool $supportMigrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! static::$supportMigrated) {
            Artisan::call('migrate', [
                '--path' => 'database/migrations/support',
                '--database' => 'support',
                '--force' => true,
            ]);
            static::$supportMigrated = true;
        }

        \DB::connection('support')->table('support_ticket_attachments')->truncate();
        \DB::connection('support')->table('support_ticket_ratings')->truncate();
        \DB::connection('support')->table('support_ticket_messages')->truncate();
        \DB::connection('support')->table('support_tickets')->truncate();
        \DB::connection('support')->table('support_ticket_categories')->truncate();
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
        $this->assertEquals(1, Ticket::on('support')->where('user_id', $user->id)->count());
        $this->assertEquals(1, TicketMessage::on('support')->count());
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

        $ticket = Ticket::on('support')->where('user_id', $user->id)->first();
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

        $ticket = Ticket::on('support')->where('user_id', $user->id)->first();

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
        $ticket = Ticket::on('support')->create([
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

        $ticket = Ticket::on('support')->create([
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

        $ticket = Ticket::on('support')->create([
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

        $ticket = Ticket::on('support')->create([
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
}

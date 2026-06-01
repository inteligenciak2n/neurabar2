<?php

namespace Tests\Feature\Support;

use App\Enums\ProfileEnum;
use App\Enums\UserRole;
use App\Models\Support\Tutorial;
use App\Models\Support\TutorialCategory;
use App\Models\Tenant\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TutorialTest extends TestCase
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

        \DB::connection('support')->table('support_tutorials')->truncate();
        \DB::connection('support')->table('support_tutorial_categories')->truncate();
    }

    public function test_guest_can_view_published_tutorials(): void
    {
        $category = TutorialCategory::create(['name' => 'Guides', 'active' => true, 'position' => 0]);
        Tutorial::create([
            'category_id' => $category->id,
            'title' => 'Getting Started',
            'slug' => 'getting-started',
            'body' => '# Hello',
            'published' => true,
            'published_at' => now(),
            'position' => 0,
        ]);

        $this->makeAuthUser();

        $this->get(route('support.tutorials.index'))->assertOk();
        $this->get(route('support.tutorials.show', 'getting-started'))->assertOk();
    }

    public function test_backoffice_agent_can_list_tutorials(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);

        $this->get(route('platform.support.tutorials.index'))->assertOk();
    }

    public function test_backoffice_agent_can_create_tutorial(): void
    {
        Storage::fake('public');

        $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);

        $category = TutorialCategory::create(['name' => 'Help', 'active' => true, 'position' => 0]);

        $this->post(route('platform.support.tutorials.store'), [
            'category_id' => $category->id,
            'title' => 'New Tutorial',
            'body' => '## Content',
            'published' => false,
            'position' => 1,
        ])->assertRedirect();

        $this->assertEquals(1, Tutorial::on('support')->where('title', 'New Tutorial')->count());
    }

    public function test_backoffice_agent_can_update_tutorial(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);

        $category = TutorialCategory::create(['name' => 'Help', 'active' => true, 'position' => 0]);
        $tutorial = Tutorial::create([
            'category_id' => $category->id,
            'title' => 'Old Title',
            'slug' => 'old-title',
            'body' => '## Old content',
            'published' => false,
            'position' => 0,
        ]);

        $this->put(route('platform.support.tutorials.update', $tutorial->id), [
            'category_id' => $category->id,
            'title' => 'Updated Title',
            'body' => '## New content',
            'published' => false,
            'position' => 0,
        ])->assertRedirect();

        $this->assertEquals('Updated Title', $tutorial->fresh()->title);
    }

    public function test_backoffice_agent_can_delete_tutorial(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);

        $category = TutorialCategory::create(['name' => 'Help', 'active' => true, 'position' => 0]);
        $tutorial = Tutorial::create([
            'category_id' => $category->id,
            'title' => 'To Delete',
            'slug' => 'to-delete',
            'body' => '## Content',
            'published' => false,
            'position' => 0,
        ]);

        $this->delete(route('platform.support.tutorials.destroy', $tutorial->id))->assertRedirect();

        $this->assertNull($tutorial->fresh());
    }

    public function test_backoffice_agent_can_toggle_published(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);

        $category = TutorialCategory::create(['name' => 'Help', 'active' => true, 'position' => 0]);
        $tutorial = Tutorial::create([
            'category_id' => $category->id,
            'title' => 'Toggle me',
            'slug' => 'toggle-me',
            'body' => '## Content',
            'published' => false,
            'position' => 0,
        ]);

        $this->post(route('platform.support.tutorials.toggle-published', $tutorial->id))->assertRedirect();

        $this->assertTrue($tutorial->fresh()->published);
    }

    private function makeAuthUser(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Attendant, $venue);
    }
}

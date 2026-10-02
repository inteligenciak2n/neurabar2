<?php

namespace Tests\Feature\Settings;

use App\Enums\UserRole;
use App\Models\Tenant\Venue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class VenueLogoTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_owner_can_upload_a_venue_logo(): void
    {
        Storage::fake('public');

        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->put(route('settings.venue.update'), [
            'name' => $venue->name,
            'require_table' => false,
            'require_tab' => false,
            'require_location' => false,
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertRedirect();

        $venue->refresh();

        $this->assertNotNull($venue->storedLogoPath());
        Storage::disk('public')->assertExists($venue->storedLogoPath());
        $this->assertSame(Storage::disk('public')->url($venue->storedLogoPath()), $venue->logo_url);
    }

    public function test_updating_the_venue_without_a_logo_keeps_the_existing_file(): void
    {
        Storage::fake('public');

        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->put(route('settings.venue.update'), [
            'name' => $venue->name,
            'require_table' => false,
            'require_tab' => false,
            'require_location' => false,
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertRedirect();

        $venue->refresh();
        $path = $venue->storedLogoPath();

        $this->put(route('settings.venue.update'), [
            'name' => 'Bar do Zé',
            'require_table' => false,
            'require_tab' => false,
            'require_location' => false,
        ])->assertRedirect();

        $venue->refresh();
        $this->assertSame('Bar do Zé', $venue->name);
        $this->assertSame($path, $venue->storedLogoPath());
        Storage::disk('public')->assertExists($path);
    }

    public function test_owner_can_replace_a_venue_logo(): void
    {
        Storage::fake('public');

        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->put(route('settings.venue.update'), [
            'name' => $venue->name,
            'require_table' => false,
            'require_tab' => false,
            'require_location' => false,
            'logo' => UploadedFile::fake()->image('old.png'),
        ])->assertRedirect();

        $venue->refresh();
        $oldPath = $venue->storedLogoPath();

        $this->put(route('settings.venue.update'), [
            'name' => $venue->name,
            'require_table' => false,
            'require_tab' => false,
            'require_location' => false,
            'logo' => UploadedFile::fake()->image('new.png'),
        ])->assertRedirect();

        $venue->refresh();
        $this->assertNotSame($oldPath, $venue->storedLogoPath());
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($venue->storedLogoPath());
    }

    public function test_owner_can_remove_a_venue_logo(): void
    {
        Storage::fake('public');

        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->put(route('settings.venue.update'), [
            'name' => $venue->name,
            'require_table' => false,
            'require_tab' => false,
            'require_location' => false,
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertRedirect();

        $venue->refresh();
        $path = $venue->storedLogoPath();

        $this->put(route('settings.venue.update'), [
            'name' => $venue->name,
            'require_table' => false,
            'require_tab' => false,
            'require_location' => false,
            'remove_logo' => true,
        ])->assertRedirect();

        $venue->refresh();
        $this->assertNull($venue->storedLogoPath());
        $this->assertNull($venue->logo_url);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_remote_logo_urls_are_not_treated_as_stored_files(): void
    {
        $venue = Venue::factory()->create([
            'active' => true,
            'logo_url' => 'https://www.instagram.com/bardozevv/',
        ]);

        $this->assertNull($venue->storedLogoPath());
        $this->assertSame('https://www.instagram.com/bardozevv/', $venue->logo_url);
    }

    public function test_non_image_logo_upload_is_rejected(): void
    {
        Storage::fake('public');

        $venue = Venue::factory()->create(['active' => true]);
        $this->loginAs(UserRole::Owner, $venue);

        $this->put(route('settings.venue.update'), [
            'name' => $venue->name,
            'require_table' => false,
            'require_tab' => false,
            'require_location' => false,
            'logo' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('logo');
    }
}

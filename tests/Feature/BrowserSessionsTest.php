<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class BrowserSessionsTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_other_browser_sessions_can_be_logged_out(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->delete('/user/other-browser-sessions', [
            'password' => 'password',
        ]);

        $response->assertSessionHasNoErrors();
    }
}

<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Welcome')
                ->has('canLogin')
                ->has('canRegister')
            )
            ->assertSee('6 motivos que compensam o investimento', false);
    }
}

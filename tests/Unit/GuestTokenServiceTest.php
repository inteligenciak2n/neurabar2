<?php

namespace Tests\Unit;

use App\Models\Tenant\Venue;
use App\Services\GuestTokenService;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class GuestTokenServiceTest extends TestCase
{
    use RefreshAllDatabases;

    private GuestTokenService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GuestTokenService::class);
    }

    public function test_decode_returns_venue(): void
    {
        $venue = Venue::factory()->create();
        $token = rtrim(base64_encode(json_encode(['v' => $venue->id])), '=');

        $result = $this->service->decode($token);

        $this->assertEquals($venue->id, $result['venue']->id);
        $this->assertNull($result['serviceLocation']);
        $this->assertNull($result['attendanceChannel']);
    }

    public function test_decode_throws_for_invalid_token(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->service->decode('invalid-base64-garbage!!!');
    }

    public function test_decode_throws_when_venue_not_found(): void
    {
        $token = rtrim(base64_encode(json_encode(['v' => Str::uuid()])), '=');
        $this->expectException(NotFoundHttpException::class);
        $this->service->decode($token);
    }

    public function test_encode_venue_only_round_trips_through_decode(): void
    {
        $venue = Venue::factory()->create();

        $result = $this->service->decode($this->service->encodeVenueOnly($venue));

        $this->assertEquals($venue->id, $result['venue']->id);
    }

    public function test_decode_rejects_a_tampered_signed_token(): void
    {
        $venueA = Venue::factory()->create();
        $venueB = Venue::factory()->create();

        $json = base64_decode($this->service->encodeVenueOnly($venueA));
        $payload = json_decode($json, true);
        $payload['v'] = $venueB->id; // swap the venue without recomputing the signature

        $tampered = rtrim(base64_encode(json_encode($payload)), '=');

        $this->expectException(NotFoundHttpException::class);
        $this->service->decode($tampered);
    }
}

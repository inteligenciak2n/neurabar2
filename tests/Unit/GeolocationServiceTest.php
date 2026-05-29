<?php

namespace Tests\Unit;

use App\Services\GeolocationService;
use Tests\TestCase;

class GeolocationServiceTest extends TestCase
{
    private GeolocationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GeolocationService;
    }

    public function test_distance_between_same_point_is_zero(): void
    {
        $distance = $this->service->distanceInMeters(-23.5505, -46.6333, -23.5505, -46.6333);
        $this->assertEqualsWithDelta(0, $distance, 0.001);
    }

    public function test_distance_between_known_points(): void
    {
        // ~84km between central São Paulo and Campinas (approximate)
        $distance = $this->service->distanceInMeters(-23.5505, -46.6333, -22.9068, -47.0626);
        $this->assertGreaterThan(50_000, $distance);
        $this->assertLessThan(200_000, $distance);
    }

    public function test_is_within_range_when_close(): void
    {
        // 0m apart — always within range
        $this->assertTrue($this->service->isWithinRange(-23.5505, -46.6333, -23.5505, -46.6333));
    }

    public function test_is_not_within_range_when_far(): void
    {
        // São Paulo × Campinas (~157km)
        $this->assertFalse($this->service->isWithinRange(-23.5505, -46.6333, -22.9068, -47.0626));
    }

    public function test_custom_radius_is_respected(): void
    {
        // ~111m per 0.001 degree latitude
        $distanceMethods = $this->service->distanceInMeters(-23.550, -46.633, -23.551, -46.633);
        $this->assertTrue($this->service->isWithinRange(-23.550, -46.633, -23.551, -46.633, 200));
        $this->assertFalse($this->service->isWithinRange(-23.550, -46.633, -23.551, -46.633, 50));
    }
}

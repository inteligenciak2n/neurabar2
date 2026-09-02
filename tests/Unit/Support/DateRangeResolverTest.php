<?php

namespace Tests\Unit\Support;

use App\Support\DateRangeResolver;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DateRangeResolverTest extends TestCase
{
    private DateRangeResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new DateRangeResolver;
        Carbon::setTestNow(Carbon::parse('2026-09-15 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_today_preset(): void
    {
        $range = $this->resolver->resolve('today');

        $this->assertTrue($range['from']->isSameDay(Carbon::today()));
        $this->assertTrue($range['to']->isSameDay(Carbon::today()));
        $this->assertTrue($range['previous_from']->isSameDay(Carbon::yesterday()));
        $this->assertTrue($range['previous_to']->isSameDay(Carbon::yesterday()));
    }

    public function test_7d_preset(): void
    {
        $range = $this->resolver->resolve('7d');

        $this->assertEquals(Carbon::today()->subDays(6)->toDateString(), $range['from']->toDateString());
        $this->assertEquals(Carbon::today()->toDateString(), $range['to']->toDateString());
        // 7 days, so the previous period is the 7 days immediately before.
        $this->assertEquals(Carbon::today()->subDays(13)->toDateString(), $range['previous_from']->toDateString());
        $this->assertEquals(Carbon::today()->subDays(7)->toDateString(), $range['previous_to']->toDateString());
    }

    public function test_month_preset(): void
    {
        $range = $this->resolver->resolve('month');

        $this->assertEquals(Carbon::today()->startOfMonth()->toDateString(), $range['from']->toDateString());
        $this->assertEquals(Carbon::today()->toDateString(), $range['to']->toDateString());
    }

    public function test_custom_preset(): void
    {
        $range = $this->resolver->resolve('custom', '2026-08-01', '2026-08-10');

        $this->assertEquals('2026-08-01', $range['from']->toDateString());
        $this->assertEquals('2026-08-10', $range['to']->toDateString());
        // 10 days range, previous period is the 10 days immediately before.
        $this->assertEquals('2026-07-22', $range['previous_from']->toDateString());
        $this->assertEquals('2026-07-31', $range['previous_to']->toDateString());
    }

    public function test_default_preset_is_30_days(): void
    {
        $range = $this->resolver->resolve('unknown-preset');

        $this->assertEquals(Carbon::today()->subDays(29)->toDateString(), $range['from']->toDateString());
        $this->assertEquals(Carbon::today()->toDateString(), $range['to']->toDateString());
    }
}

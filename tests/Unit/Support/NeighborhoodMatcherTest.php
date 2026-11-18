<?php

namespace Tests\Unit\Support;

use App\Support\NeighborhoodMatcher;
use PHPUnit\Framework\TestCase;

class NeighborhoodMatcherTest extends TestCase
{
    public function test_it_matches_neighborhoods_ignoring_case_and_accents(): void
    {
        $zones = [
            ['id' => '1', 'name' => 'Jardim São Paulo'],
            ['id' => '2', 'name' => 'Centro'],
        ];

        $match = NeighborhoodMatcher::match('jardim sao paulo', $zones);

        $this->assertSame('1', $match['id']);
    }

    public function test_it_expands_common_abbreviations(): void
    {
        $zones = [
            ['id' => 'jd', 'name' => 'Jardim Palhano'],
            ['id' => 'vl', 'name' => 'Vila Casoni'],
            ['id' => 'pq', 'name' => 'Parque Guanabara'],
        ];

        $this->assertSame('jd', NeighborhoodMatcher::match('Jd. Palhano', $zones)['id']);
        $this->assertSame('vl', NeighborhoodMatcher::match('Vl. Casoni', $zones)['id']);
        $this->assertSame('pq', NeighborhoodMatcher::match('Pq. Guanabara', $zones)['id']);
    }

    public function test_it_returns_null_when_there_is_no_match(): void
    {
        $this->assertNull(NeighborhoodMatcher::match('Gleba Palhano', [
            ['id' => '1', 'name' => 'Centro'],
        ]));
    }
}

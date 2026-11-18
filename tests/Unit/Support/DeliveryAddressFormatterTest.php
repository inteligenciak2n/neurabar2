<?php

namespace Tests\Unit\Support;

use App\Support\DeliveryAddressFormatter;
use PHPUnit\Framework\TestCase;

class DeliveryAddressFormatterTest extends TestCase
{
    public function test_it_builds_a_readable_line_omitting_empty_fields(): void
    {
        $line = DeliveryAddressFormatter::line([
            'street' => 'Rua X',
            'number' => '120',
            'complement' => 'Apto 32',
            'neighborhood' => 'Bairro Y',
            'reference_point' => 'portão azul',
            'delivery_phone' => '43999999999',
        ]);

        $this->assertSame(
            'Rua X, 120, Apto 32 – Bairro Y – (ref: portão azul) – Tel: (43) 99999-9999',
            $line,
        );
    }

    public function test_it_builds_a_google_maps_url(): void
    {
        $url = DeliveryAddressFormatter::mapsUrl([
            'street' => 'Rua X',
            'number' => '120',
            'neighborhood' => 'Centro',
            'city' => 'Londrina',
            'state' => 'PR',
        ]);

        $this->assertSame(
            'https://www.google.com/maps/search/?api=1&query='.urlencode('Rua X, 120, Centro, Londrina, PR'),
            $url,
        );
    }
}

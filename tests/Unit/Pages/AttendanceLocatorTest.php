<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class AttendanceLocatorTest extends TestCase
{
    public function test_locator_matches_selected_fields(): void
    {
        $root = dirname(__DIR__, 3);
        $util = $root.'/resources/js/Utils/attendanceLocator.js';

        $this->assertFileExists($util);
        $source = file_get_contents($util);
        $this->assertStringContainsString('export function attendanceMatchesLocator', $source);
        $this->assertStringContainsString("['tables', 'tab', 'order', 'name', 'product']", $source);

        $script = <<<'JS'
import { attendanceMatchesLocator } from './resources/js/Utils/attendanceLocator.js';

const attendance = {
    customer_identifier: 'Joao',
    tab: { code: 'A15' },
    service_location: { name: 'Mesa 2' },
    orders: [
        {
            order_number: 12,
            items: [{ product: { name: 'Suco de laranja' }, combo: null }],
        },
    ],
};

process.stdout.write(JSON.stringify({
    all: attendanceMatchesLocator(attendance, 'laranja'),
    product: attendanceMatchesLocator(attendance, 'laranja', ['product']),
    tablesOnly: attendanceMatchesLocator(attendance, 'laranja', ['tables']),
    table: attendanceMatchesLocator(attendance, 'mesa 2', ['tables']),
    tab: attendanceMatchesLocator(attendance, 'a15', ['tab']),
    order: attendanceMatchesLocator(attendance, '12', ['order']),
    name: attendanceMatchesLocator(attendance, 'joao', ['name']),
}));
JS;

        $process = proc_open(
            ['node', '--input-type=module', '-e', $script],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $root,
        );

        $this->assertIsResource($process);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $status = proc_close($process);

        $this->assertSame(0, $status, $stderr);
        $result = json_decode($stdout, true, flags: JSON_THROW_ON_ERROR);

        $this->assertTrue($result['all']);
        $this->assertTrue($result['product']);
        $this->assertFalse($result['tablesOnly']);
        $this->assertTrue($result['table']);
        $this->assertTrue($result['tab']);
        $this->assertTrue($result['order']);
        $this->assertTrue($result['name']);
    }
}

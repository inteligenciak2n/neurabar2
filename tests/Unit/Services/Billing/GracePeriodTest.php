<?php

namespace Tests\Unit\Services\Billing;

use App\Services\Billing\GracePeriod;
use InvalidArgumentException;
use Tests\TestCase;

class GracePeriodTest extends TestCase
{
    public function test_it_builds_the_expression_for_qualified_columns(): void
    {
        $this->assertSame(
            "venue_invoices.due_date + INTERVAL '1 day' * corporation_subscriptions.grace_period_days <= ?",
            GracePeriod::expression('venue_invoices.due_date', 'corporation_subscriptions.grace_period_days')
        );
    }

    public function test_it_rejects_identifiers_that_are_not_plain_columns(): void
    {
        $this->expectException(InvalidArgumentException::class);

        GracePeriod::expression('due_date); drop table users; --', 'grace_period_days');
    }
}

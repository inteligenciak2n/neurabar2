<?php

namespace Tests\Unit\Enums;

use App\Enums\InvoiceStatus;
use PHPUnit\Framework\TestCase;

class InvoiceStatusTest extends TestCase
{
    public function test_enum_values(): void
    {
        $this->assertSame('open', InvoiceStatus::Open->value);
        $this->assertSame('overdue', InvoiceStatus::Overdue->value);
        $this->assertSame('paid', InvoiceStatus::Paid->value);
        $this->assertSame('canceled', InvoiceStatus::Canceled->value);
        $this->assertSame('refunded', InvoiceStatus::Refunded->value);
    }

    public function test_can_transition_to_paid(): void
    {
        $this->assertTrue(InvoiceStatus::Open->canTransitionTo(InvoiceStatus::Paid));
        $this->assertTrue(InvoiceStatus::Overdue->canTransitionTo(InvoiceStatus::Paid));
        $this->assertFalse(InvoiceStatus::Paid->canTransitionTo(InvoiceStatus::Paid));
        $this->assertFalse(InvoiceStatus::Canceled->canTransitionTo(InvoiceStatus::Paid));
    }

    public function test_finalized_statuses(): void
    {
        $this->assertTrue(InvoiceStatus::Paid->isFinalized());
        $this->assertTrue(InvoiceStatus::Canceled->isFinalized());
        $this->assertTrue(InvoiceStatus::Refunded->isFinalized());
        $this->assertFalse(InvoiceStatus::Open->isFinalized());
        $this->assertFalse(InvoiceStatus::Overdue->isFinalized());
    }

    public function test_labels(): void
    {
        $this->assertSame('Aberta', InvoiceStatus::Open->label());
        $this->assertSame('Vencida', InvoiceStatus::Overdue->label());
        $this->assertSame('Paga', InvoiceStatus::Paid->label());
        $this->assertSame('Cancelada', InvoiceStatus::Canceled->label());
        $this->assertSame('Reembolsada', InvoiceStatus::Refunded->label());
    }
}

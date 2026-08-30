<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Invoice::class)]
#[CoversClass(InvoiceItem::class)]
final class InvoiceItemTest extends TestCase
{
    public function testDraftTotalsAreCalculatedInIntegerCents(): void
    {
        $invoice = new Invoice('FAC-2026-000001', new \DateTimeImmutable('2026-01-15'));
        $invoice->addItem(new InvoiceItem('Consulting', 2, 1000, 2100));
        $invoice->addItem(new InvoiceItem('Hosting', 1, 500, 2100));

        self::assertSame(2500, $invoice->subtotalCents);
        self::assertSame(525, $invoice->taxTotalCents);
        self::assertSame(3025, $invoice->grandTotalCents);
    }
}

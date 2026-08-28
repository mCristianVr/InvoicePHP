<?php

declare(strict_types=1);

namespace App\Entity;

use App\Exception\InvoiceDomainException;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'invoice_item')]
#[ORM\Index(name: 'idx_invoice_item_invoice_id', columns: ['invoice_id'])]
final class InvoiceItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    public private(set) ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'invoice_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public private(set) ?Invoice $invoice = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public private(set) string $description;

    #[ORM\Column(type: Types::BIGINT)]
    public private(set) int $quantity;

    #[ORM\Column(name: 'unit_price_cents', type: Types::BIGINT)]
    public private(set) int $unitPriceCents;

    #[ORM\Column(name: 'tax_rate_basis_points', type: Types::INTEGER)]
    public private(set) int $taxRateBasisPoints;

    #[ORM\Column(name: 'line_subtotal_cents', type: Types::BIGINT)]
    public private(set) int $lineSubtotalCents = 0;

    #[ORM\Column(name: 'line_tax_cents', type: Types::BIGINT)]
    public private(set) int $lineTaxCents = 0;

    #[ORM\Column(name: 'line_total_cents', type: Types::BIGINT)]
    public private(set) int $lineTotalCents = 0;

    public function __construct(string $description, int $quantity, int $unitPriceCents, int $taxRateBasisPoints)
    {
        $this->description = trim($description);
        $this->quantity = $quantity;
        $this->unitPriceCents = $unitPriceCents;
        $this->taxRateBasisPoints = $taxRateBasisPoints;

        $this->assertValidity();
        $this->recalculateLineTotals();
    }

    public function attachToInvoice(Invoice $invoice): void
    {
        $this->invoice = $invoice;
    }

    public function detachInvoice(): void
    {
        $this->invoice = null;
    }

    public function changeDescription(string $description): void
    {
        $this->assertInvoiceMutable();

        $description = trim($description);
        if ($description === '') {
            throw new InvoiceDomainException('Invoice item description cannot be empty.');
        }

        $this->description = $description;
    }

    public function changeQuantity(int $quantity): void
    {
        $this->assertInvoiceMutable();

        $this->quantity = $quantity;
        $this->assertValidity();
        $this->recalculateLineTotals();
        $this->invoice?->recalculateTotals();
    }

    public function changeUnitPriceCents(int $unitPriceCents): void
    {
        $this->assertInvoiceMutable();

        $this->unitPriceCents = $unitPriceCents;
        $this->assertValidity();
        $this->recalculateLineTotals();
        $this->invoice?->recalculateTotals();
    }

    public function changeTaxRateBasisPoints(int $taxRateBasisPoints): void
    {
        $this->assertInvoiceMutable();

        $this->taxRateBasisPoints = $taxRateBasisPoints;
        $this->assertValidity();
        $this->recalculateLineTotals();
        $this->invoice?->recalculateTotals();
    }

    private function recalculateLineTotals(): void
    {
        $lineSubtotal = $this->quantity * $this->unitPriceCents;
        $lineTax = (int) round(($lineSubtotal * $this->taxRateBasisPoints) / 10_000, 0, PHP_ROUND_HALF_UP);

        $this->lineSubtotalCents = $lineSubtotal;
        $this->lineTaxCents = $lineTax;
        $this->lineTotalCents = $lineSubtotal + $lineTax;
    }

    private function assertValidity(): void
    {
        if ($this->description === '') {
            throw new InvoiceDomainException('Invoice item description cannot be empty.');
        }

        if ($this->quantity <= 0) {
            throw new InvoiceDomainException('Item quantity must be greater than zero.');
        }

        if ($this->unitPriceCents < 0) {
            throw new InvoiceDomainException('Unit price in cents cannot be negative.');
        }

        if ($this->taxRateBasisPoints < 0 || $this->taxRateBasisPoints > 10_000) {
            throw new InvoiceDomainException('Tax rate basis points must be between 0 and 10000.');
        }
    }

    private function assertInvoiceMutable(): void
    {
        $this->invoice?->ensureFinanciallyMutable();
    }
}

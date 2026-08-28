<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'invoice_rectification')]
#[ORM\UniqueConstraint(name: 'uniq_rectification_number', columns: ['rectification_number'])]
final class InvoiceRectification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    public private(set) ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Invoice::class)]
    #[ORM\JoinColumn(name: 'original_invoice_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    public private(set) Invoice $originalInvoice;

    #[ORM\Column(name: 'rectification_number', type: Types::STRING, length: 50)]
    public private(set) string $rectificationNumber;

    #[ORM\Column(type: Types::TEXT)]
    public private(set) string $reason;

    #[ORM\Column(name: 'adjustment_subtotal_cents', type: Types::BIGINT)]
    public private(set) int $adjustmentSubtotalCents;

    #[ORM\Column(name: 'adjustment_tax_cents', type: Types::BIGINT)]
    public private(set) int $adjustmentTaxCents;

    #[ORM\Column(name: 'adjustment_total_cents', type: Types::BIGINT)]
    public private(set) int $adjustmentTotalCents;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $createdAt;

    public function __construct(Invoice $originalInvoice, string $rectificationNumber, string $reason, int $adjustmentSubtotalCents, int $adjustmentTaxCents)
    {
        $this->originalInvoice = $originalInvoice;
        $this->rectificationNumber = trim($rectificationNumber);
        $this->reason = trim($reason);
        $this->adjustmentSubtotalCents = $adjustmentSubtotalCents;
        $this->adjustmentTaxCents = $adjustmentTaxCents;
        $this->adjustmentTotalCents = $adjustmentSubtotalCents + $adjustmentTaxCents;
        $this->createdAt = new \DateTimeImmutable();
    }
}

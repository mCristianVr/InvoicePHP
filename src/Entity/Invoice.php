<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\InvoiceStatus;
use App\Exception\InvoiceDomainException;
use App\Repository\InvoiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\Table(name: 'invoice')]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'uniq_invoice_number', columns: ['invoice_number'])]
#[ORM\Index(name: 'idx_invoice_status_issued_at', columns: ['status', 'issued_at'])]
final class Invoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    public private(set) ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'invoices')]
    #[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'id', nullable: true)]
    public private(set) ?Customer $customer = null;

    #[ORM\ManyToOne(targetEntity: InvoiceSeries::class)]
    #[ORM\JoinColumn(name: 'invoice_series_id', referencedColumnName: 'id', nullable: true)]
    public private(set) ?InvoiceSeries $invoiceSeries = null;

    #[ORM\Column(name: 'invoice_number', type: Types::STRING, length: 50)]
    public private(set) string $invoiceNumber;

    #[ORM\Column(name: 'issued_at', type: Types::DATE_IMMUTABLE)]
    public private(set) \DateTimeImmutable $issuedAt;

    #[ORM\Column(type: Types::STRING, enumType: InvoiceStatus::class, length: 16)]
    public private(set) InvoiceStatus $status = InvoiceStatus::DRAFT;

    #[ORM\Column(type: Types::STRING, length: 3)]
    public private(set) string $currency = 'EUR';

    #[ORM\Column(name: 'subtotal_cents', type: Types::BIGINT)]
    public private(set) int $subtotalCents = 0;

    #[ORM\Column(name: 'tax_total_cents', type: Types::BIGINT)]
    public private(set) int $taxTotalCents = 0;

    #[ORM\Column(name: 'grand_total_cents', type: Types::BIGINT)]
    public private(set) int $grandTotalCents = 0;

    #[ORM\Column(name: 'previous_invoice_hash', type: Types::STRING, length: 64, nullable: true)]
    public private(set) ?string $previousInvoiceHash = null;

    #[ORM\Column(name: 'current_invoice_hash', type: Types::STRING, length: 64, nullable: true, unique: true)]
    public private(set) ?string $currentInvoiceHash = null;

    #[ORM\Column(name: 'finalized_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $finalizedAt = null;

    #[ORM\Column(name: 'recipient_status_updated_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $recipientStatusUpdatedAt = null;

    #[ORM\Column(name: 'recipient_status_note', type: Types::TEXT, nullable: true)]
    public private(set) ?string $recipientStatusNote = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $updatedAt;

    /** @var Collection<int, InvoiceItem> */
    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: InvoiceItem::class, orphanRemoval: true, cascade: ['persist'])]
    #[ORM\OrderBy(['id' => 'ASC'])]
    public private(set) Collection $items;

    /** @var Collection<int, InvoiceStatusTransition> */
    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: InvoiceStatusTransition::class, orphanRemoval: true, cascade: ['persist'])]
    #[ORM\OrderBy(['changedAt' => 'ASC'])]
    public private(set) Collection $statusTransitions;

    public function __construct(string $invoiceNumber, \DateTimeImmutable $issuedAt, string $currency = 'EUR', ?Customer $customer = null, ?InvoiceSeries $invoiceSeries = null)
    {
        $this->invoiceNumber = trim($invoiceNumber);
        $this->issuedAt = $issuedAt;
        $this->currency = strtoupper(trim($currency));
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
        $this->items = new ArrayCollection();
        $this->statusTransitions = new ArrayCollection();

        if ($customer !== null) {
            $this->customer = $customer;
        }

        if ($invoiceSeries !== null) {
            $this->invoiceSeries = $invoiceSeries;
        }

        if ($this->invoiceNumber === '') {
            throw new InvoiceDomainException('Invoice number cannot be empty.');
        }

        if ($this->currency === '' || strlen($this->currency) !== 3) {
            throw new InvoiceDomainException('Currency must be a 3-letter ISO code.');
        }
    }

    public function assignCustomer(Customer $customer): void
    {
        $this->assertFinanciallyMutable();
        $this->customer = $customer;
        $this->touch();
    }

    public function assignSeries(InvoiceSeries $invoiceSeries): void
    {
        $this->assertFinanciallyMutable();
        $this->invoiceSeries = $invoiceSeries;
        $this->touch();
    }

    public function assignInvoiceNumber(string $invoiceNumber, ?\DateTimeImmutable $issuedAt = null): void
    {
        $this->assertFinanciallyMutable();
        $invoiceNumber = trim($invoiceNumber);

        if ($invoiceNumber === '') {
            throw new InvoiceDomainException('Invoice number cannot be empty.');
        }

        $this->invoiceNumber = $invoiceNumber;
        if ($issuedAt !== null) {
            $this->issuedAt = $issuedAt;
        }
        $this->touch();
    }

    public function addItem(InvoiceItem $item): void
    {
        $this->assertFinanciallyMutable();

        if ($this->items->contains($item)) {
            return;
        }

        $this->items->add($item);
        $item->attachToInvoice($this);
        $this->recalculateTotals();
        $this->touch();
    }

    public function removeItem(InvoiceItem $item): void
    {
        $this->assertFinanciallyMutable();

        if (!$this->items->removeElement($item)) {
            return;
        }

        $item->detachInvoice();
        $this->recalculateTotals();
        $this->touch();
    }

    public function ensureFinanciallyMutable(): void
    {
        $this->assertFinanciallyMutable();
    }

    public function registerRecipientStatus(InvoiceStatus $newStatus, \DateTimeImmutable $changedAt, ?string $note = null): void
    {
        if ($newStatus === InvoiceStatus::DRAFT) {
            throw new InvoiceDomainException('Recipient status updates cannot move an invoice back to DRAFT.');
        }

        if ($this->status === InvoiceStatus::DRAFT && $newStatus !== InvoiceStatus::SENT) {
            throw new InvoiceDomainException('A draft invoice must be SENT before other recipient states are accepted.');
        }

        $previous = $this->status;
        $this->status = $newStatus;
        $this->recipientStatusUpdatedAt = $changedAt;
        $this->recipientStatusNote = $note;
        $this->statusTransitions->add(new InvoiceStatusTransition($this, $previous, $newStatus, $changedAt, $note));
        $this->touch();
    }

    public function finalizeForChaining(?string $previousHash = null, ?string $currentHash = null, ?\DateTimeImmutable $finalizedAt = null): void
    {
        if ($this->status !== InvoiceStatus::DRAFT) {
            throw new InvoiceDomainException('Only draft invoices can be finalized.');
        }

        if ($this->items->isEmpty()) {
            throw new InvoiceDomainException('An invoice must contain at least one line item before finalization.');
        }

        $finalizedAt ??= new \DateTimeImmutable();
        $this->previousInvoiceHash = $previousHash ?? $this->previousInvoiceHash;
        $this->currentInvoiceHash = $currentHash ?? $this->currentInvoiceHash;
        $this->finalizedAt = $finalizedAt;

        $this->registerRecipientStatus(InvoiceStatus::SENT, $finalizedAt, 'Invoice finalized and chained (VeriFactu).');
    }

    public function markPaid(\DateTimeImmutable $paidAt): void
    {
        if ($this->status === InvoiceStatus::DRAFT) {
            throw new InvoiceDomainException('Draft invoices cannot be marked as PAID.');
        }

        $this->registerRecipientStatus(InvoiceStatus::PAID, $paidAt, 'Payment confirmed.');
    }

    public function recalculateTotals(): void
    {
        $this->assertFinanciallyMutable();

        $subtotal = 0;
        $taxTotal = 0;

        foreach ($this->items as $item) {
            $subtotal += $item->lineSubtotalCents;
            $taxTotal += $item->lineTaxCents;
        }

        $this->subtotalCents = $subtotal;
        $this->taxTotalCents = $taxTotal;
        $this->grandTotalCents = $subtotal + $taxTotal;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function assertFinanciallyMutable(): void
    {
        // DB-level trigger backstop is a Sprint 2 responsibility; app-layer guard remains until then.
        if ($this->finalizedAt !== null || $this->status === InvoiceStatus::PAID || $this->status === InvoiceStatus::SENT) {
            throw new InvoiceDomainException('Financial data is immutable after invoice has been SENT or PAID. Use a rectification document instead.');
        }
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}

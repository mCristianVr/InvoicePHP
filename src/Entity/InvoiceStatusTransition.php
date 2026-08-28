<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\InvoiceStatus;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'invoice_status_transition')]
#[ORM\Index(name: 'idx_invoice_status_transition_invoice_id_changed_at', columns: ['invoice_id', 'changed_at'])]
final class InvoiceStatusTransition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    public private(set) ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'statusTransitions')]
    #[ORM\JoinColumn(name: 'invoice_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public private(set) Invoice $invoice;

    #[ORM\Column(name: 'from_status', type: Types::STRING, enumType: InvoiceStatus::class, length: 16, nullable: true)]
    public private(set) ?InvoiceStatus $fromStatus;

    #[ORM\Column(name: 'to_status', type: Types::STRING, enumType: InvoiceStatus::class, length: 16)]
    public private(set) InvoiceStatus $toStatus;

    #[ORM\Column(name: 'changed_at', type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $changedAt;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public private(set) ?string $note;

    public function __construct(Invoice $invoice, ?InvoiceStatus $fromStatus, InvoiceStatus $toStatus, \DateTimeImmutable $changedAt, ?string $note = null)
    {
        $this->invoice = $invoice;
        $this->fromStatus = $fromStatus;
        $this->toStatus = $toStatus;
        $this->changedAt = $changedAt;
        $this->note = $note;
    }
}

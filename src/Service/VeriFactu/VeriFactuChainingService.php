<?php

declare(strict_types=1);

namespace App\Service\VeriFactu;

use App\Entity\Invoice;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class VeriFactuChainingService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private InvoiceRepository $invoiceRepository,
    ) {
    }

    public function finalizeAndChainInvoice(Invoice $invoice, ?\DateTimeImmutable $finalizedAt = null): void
    {
        $finalizedAt ??= new \DateTimeImmutable();

        $previousHash = $this->invoiceRepository->findLastFinalizedHash() ?? str_repeat('0', 64);

        $payload = [
            'invoice_number' => $invoice->invoiceNumber,
            'invoice_date' => $invoice->issuedAt->format('Y-m-d'),
            'tax_total_cents' => $invoice->taxTotalCents,
            'grand_total_cents' => $invoice->grandTotalCents,
            'previous_invoice_hash' => $previousHash,
        ];

        $currentHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        $invoice->finalizeForChaining($previousHash, $currentHash, $finalizedAt);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();
    }
}

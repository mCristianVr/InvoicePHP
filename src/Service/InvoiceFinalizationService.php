<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Invoice;
use App\Entity\InvoiceSeries;
use App\Enum\InvoiceStatus;
use App\Exception\InvoiceDomainException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;

final class InvoiceFinalizationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Connection $connection,
    ) {
    }

    public function finalizeDraft(Invoice $invoice, InvoiceSeries $series, ?\DateTimeImmutable $finalizedAt = null): void
    {
        if ($invoice->status !== InvoiceStatus::DRAFT) {
            throw new InvoiceDomainException('Only draft invoices can be finalized.');
        }

        if ($invoice->items->isEmpty()) {
            throw new InvoiceDomainException('An invoice must contain at least one line item before finalization.');
        }

        $finalizedAt ??= new \DateTimeImmutable();

        $this->connection->beginTransaction();

        try {
            $platform = $this->connection->getDatabasePlatform();
            if ($platform instanceof PostgreSQLPlatform) {
                $this->connection->executeStatement('SELECT pg_advisory_xact_lock(:seriesId)', ['seriesId' => (int) $series->id]);
            } else {
                $this->connection->executeStatement('SELECT id FROM invoice_series WHERE id = :seriesId FOR UPDATE', ['seriesId' => (int) $series->id]);
            }

            $managedSeries = $this->entityManager->find(InvoiceSeries::class, $series->id);
            if (!$managedSeries instanceof InvoiceSeries) {
                throw new InvoiceDomainException('Invoice series not found.');
            }

            $nextNumber = $managedSeries->nextNumber();
            $invoice->assignSeries($managedSeries);
            $invoice->assignInvoiceNumber($managedSeries->formatNumber($nextNumber), $finalizedAt);

            // Keep the hash seam clean for Sprint 2. The chain payload is intentionally left out here.
            $invoice->finalizeForChaining(null, null, $finalizedAt);

            $this->entityManager->persist($managedSeries);
            $this->entityManager->persist($invoice);
            $this->entityManager->flush();
            $this->connection->commit();
        } catch (\Throwable $throwable) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            throw $throwable;
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Customer;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Entity\InvoiceSeries;
use App\Service\InvoiceFinalizationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Process\Process;

#[CoversClass(InvoiceFinalizationService::class)]
final class InvoiceFinalizationServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $this->entityManager->beginTransaction();
        $this->entityManager->getConnection()->executeStatement('DELETE FROM invoice_status_transition');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM invoice_item');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM invoice');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM invoice_series');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM customer');
    }

    protected function tearDown(): void
    {
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }

        parent::tearDown();
    }

    public function testConcurrentFinalizationProducesSequentialNumbersWithoutGaps(): void
    {
        if (!extension_loaded('pdo_pgsql')) {
            self::markTestSkipped('PostgreSQL support is required for concurrent finalization tests.');
        }

        $customer = new Customer('Acme SL', 'A58818501', 'Calle Mayor 1, Madrid', 'info@example.com', '912345678');
        $series = new InvoiceSeries('FAC', 2026, 1);

        $this->entityManager->persist($customer);
        $this->entityManager->persist($series);
        $this->entityManager->flush();

        $processes = [];
        for ($i = 0; $i < 8; ++$i) {
            $processes[] = new Process([
                'php',
                'bin/console',
                'app:finalize-draft',
                '--series-id=' . $series->id,
                '--customer-id=' . $customer->id,
                '--issuer=' . '2026-01-15',
            ]);
        }

        foreach ($processes as $process) {
            $process->start();
        }

        foreach ($processes as $process) {
            $process->wait();
            self::assertSame(0, $process->getExitCode(), $process->getErrorOutput() ?: $process->getOutput());
        }

        $numbers = $this->entityManager->getConnection()->fetchFirstColumn('SELECT invoice_number FROM invoice WHERE invoice_series_id = :series ORDER BY invoice_number ASC', ['series' => $series->id]);

        self::assertCount(8, $numbers);
        self::assertSame(['FAC-2026-000001', 'FAC-2026-000002', 'FAC-2026-000003', 'FAC-2026-000004', 'FAC-2026-000005', 'FAC-2026-000006', 'FAC-2026-000007', 'FAC-2026-000008'], $numbers);
    }
}

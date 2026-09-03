<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Invoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invoice>
 */
final class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    public function findLastFinalizedHash(): ?string
    {
        $row = $this->createQueryBuilder('i')
            ->select('i.currentInvoiceHash AS hash')
            ->where('i.currentInvoiceHash IS NOT NULL')
            ->andWhere('i.finalizedAt IS NOT NULL')
            ->orderBy('i.finalizedAt', 'DESC')
            ->addOrderBy('i.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $row['hash'] ?? null;
    }

    /** @return array{draft_total:int,sent_total:int,paid_total:int} */
    public function dashboardTotals(): array
    {
        $rows = $this->createQueryBuilder('i')
            ->select('i.status AS status', 'SUM(i.grandTotalCents) AS total')
            ->groupBy('i.status')
            ->getQuery()
            ->getArrayResult();

        $totals = [
            'DRAFT' => 0,
            'SENT' => 0,
            'PAID' => 0,
        ];

        foreach ($rows as $row) {
            $status = $row['status'];
            if (isset($totals[$status])) {
                $totals[$status] = (int) $row['total'];
            }
        }

        return [
            'draft_total' => $totals['DRAFT'],
            'sent_total' => $totals['SENT'],
            'paid_total' => $totals['PAID'],
        ];
    }

    /** @return array{ALL:int,DRAFT:int,SENT:int,PAID:int,OVERDUE:int,REJECTED:int} */
    public function dashboardStatusCounts(): array
    {
        $rows = $this->createQueryBuilder('i')
            ->select('i.status AS status', 'COUNT(i.id) AS total')
            ->groupBy('i.status')
            ->getQuery()
            ->getArrayResult();

        $counts = [
            'DRAFT' => 0,
            'SENT' => 0,
            'PAID' => 0,
            'OVERDUE' => 0,
            'REJECTED' => 0,
        ];

        foreach ($rows as $row) {
            $status = $row['status'];
            if (isset($counts[$status])) {
                $counts[$status] = (int) $row['total'];
            }
        }

        return [
            'ALL' => array_sum($counts),
            'DRAFT' => $counts['DRAFT'],
            'SENT' => $counts['SENT'],
            'PAID' => $counts['PAID'],
            'OVERDUE' => $counts['OVERDUE'],
            'REJECTED' => $counts['REJECTED'],
        ];
    }
}

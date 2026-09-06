<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Invoice;
use App\Entity\User;
use App\Enum\InvoiceStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    /** @return list<Invoice> */
    public function findVisibleForDashboard(User $actor, ?string $statusFilter = null): array
    {
        $qb = $this->createVisibleToActorQueryBuilder($actor)
            ->orderBy('i.issuedAt', 'DESC')
            ->addOrderBy('i.id', 'DESC');

        if ($statusFilter !== null && $statusFilter !== 'ALL') {
            $qb
                ->andWhere('i.status = :status')
                ->setParameter('status', $statusFilter);
        }

        return $qb->getQuery()->getResult();
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
    public function dashboardTotals(User $actor): array
    {
        $rows = $this->createVisibleToActorQueryBuilder($actor)
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
            $status = $this->normalizeStatusKey($row['status'] ?? null);
            if ($status === null) {
                continue;
            }

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
    public function dashboardStatusCounts(User $actor): array
    {
        $rows = $this->createVisibleToActorQueryBuilder($actor)
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
            $status = $this->normalizeStatusKey($row['status'] ?? null);
            if ($status === null) {
                continue;
            }

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

    public function invoiceNumberExists(string $invoiceNumber): bool
    {
        return $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.invoiceNumber = :invoiceNumber')
            ->setParameter('invoiceNumber', $invoiceNumber)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    private function createVisibleToActorQueryBuilder(User $actor): QueryBuilder
    {
        $qb = $this->createQueryBuilder('i')
            ->leftJoin('i.customer', 'c');

        if (in_array('ROLE_ADMIN', $actor->getRoles(), true)) {
            return $qb;
        }

        return $qb
            ->andWhere('c.owner = :owner')
            ->setParameter('owner', $actor);
    }

    private function normalizeStatusKey(mixed $status): ?string
    {
        if ($status instanceof InvoiceStatus) {
            return $status->value;
        }

        if (is_string($status) && $status !== '') {
            return strtoupper($status);
        }

        return null;
    }
}

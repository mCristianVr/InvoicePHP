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
}

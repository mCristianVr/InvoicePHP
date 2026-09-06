<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Customer>
 */
final class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    /** @return list<Customer> */
    public function findAllVisibleTo(User $actor): array
    {
        return $this->createVisibleToActorQueryBuilder($actor)
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneVisibleTo(User $actor, int $id): ?Customer
    {
        return $this->createVisibleToActorQueryBuilder($actor)
            ->andWhere('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function ownerHasNifCif(User $owner, string $nifCif): bool
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.owner = :owner')
            ->andWhere('c.nifCif = :nifCif')
            ->setParameter('owner', $owner)
            ->setParameter('nifCif', strtoupper(trim($nifCif)))
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /** Public so form/autocomplete layers can reuse the same owner-scoping rule. */
    public function createVisibleToActorQueryBuilder(User $actor): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c');

        // Admin access is an explicit exception: they can inspect all owners.
        if (in_array('ROLE_ADMIN', $actor->getRoles(), true)) {
            return $qb;
        }

        return $qb
            ->andWhere('c.owner = :owner')
            ->setParameter('owner', $actor);
    }
}

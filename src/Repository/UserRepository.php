<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function countTotalUsers(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countStaffUsers(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_STAFF%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAdminUsers(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countCustomerUsers(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles NOT LIKE :staff AND u.roles NOT LIKE :admin')
            ->setParameter('staff', '%ROLE_STAFF%')
            ->setParameter('admin', '%ROLE_ADMIN%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return User[]
     */
    public function findAllOrderedByLatest(): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

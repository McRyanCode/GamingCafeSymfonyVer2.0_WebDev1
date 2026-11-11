<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function countTotalBookings(): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countActiveBookings(): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.status = :status')
            ->setParameter('status', 'CONFIRMED')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Booking[]
     */
    public function findRecentBookings(int $limit = 5): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.user', 'u')
            ->addSelect('u')
            ->orderBy('b.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Booking[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.user = :user')
            ->setParameter('user', $user)
            ->orderBy('b.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

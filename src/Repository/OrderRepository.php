<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * @return array<string, int> Nombre de commandes par statut
     */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('o.status, COUNT(o.id) as nb')
            ->groupBy('o.status')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['nb'];
        }

        return $counts;
    }

    /** Chiffre d'affaires encaissé : commandes payées, expédiées ou non. */
    public function sumPaidTotal(): string
    {
        return $this->createQueryBuilder('o')
            ->select('COALESCE(SUM(o.total), 0)')
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('statuses', [Order::STATUS_COMPLETED, Order::STATUS_SHIPPED])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Commandes jamais payées (en attente ou paiement commencé) créées avant la date donnée.
     *
     * @return Order[]
     */
    public function findPayableCreatedBefore(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.status IN (:statuses)')
            ->andWhere('o.createdAt < :date')
            ->setParameter('statuses', [Order::STATUS_PENDING, Order::STATUS_PROCESSING])
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Order[]
     */
    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

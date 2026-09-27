<?php

namespace App\Repository;

use App\Entity\WaitlistSubscriber;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WaitlistSubscriber>
 */
class WaitlistSubscriberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WaitlistSubscriber::class);
    }

    public function isSubscribed(string $email): bool
    {
        return null !== $this->findOneBy(['email' => mb_strtolower(trim($email))]);
    }
}

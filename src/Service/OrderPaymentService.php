<?php

namespace App\Service;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Passe une commande à « payée ». Le retour du client et le webhook Stripe arrivent
 * souvent en même temps : la mise à jour conditionnelle garantit qu'un seul des deux
 * gagne, et donc que les e-mails ne partent qu'une fois.
 */
class OrderPaymentService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderNotifier $notifier,
    ) {
    }

    /** @return bool true si c'est cet appel qui a validé la commande */
    public function confirmPayment(Order $order): bool
    {
        $updated = $this->entityManager->createQueryBuilder()
            ->update(Order::class, 'o')
            ->set('o.status', ':paid')
            ->where('o.id = :id')
            ->andWhere('o.status = :processing')
            ->setParameter('paid', Order::STATUS_COMPLETED)
            ->setParameter('processing', Order::STATUS_PROCESSING)
            ->setParameter('id', $order->getId())
            ->getQuery()
            ->execute();

        $this->entityManager->refresh($order);

        if (1 !== $updated) {
            return false;
        }

        $this->notifier->sendPaymentConfirmation($order);

        return true;
    }
}

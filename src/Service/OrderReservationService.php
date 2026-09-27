<?php

namespace App\Service;

use App\Entity\Order;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Annule une commande non payée et remet ses pièces uniques en vente.
 */
class OrderReservationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderRepository $orderRepository,
        private readonly CartRepository $cartRepository,
    ) {
    }

    /**
     * @param bool $restoreToCart remet aussi les pièces dans le panier du client
     *                            (utile quand l'échec vient de nous, pas de lui)
     */
    public function cancel(Order $order, bool $restoreToCart = false): void
    {
        if (!$order->isPayable()) {
            return;
        }

        $cart = $restoreToCart && $order->getUser()
            ? $this->cartRepository->getOrCreateForUser($order->getUser())
            : null;

        foreach ($order->getOrderItems() as $orderItem) {
            $product = $orderItem->getProduct();
            if (!$product) {
                continue;
            }
            $product->setAvailable(true);
            $cart?->addProduct($product);
        }

        $order->setStatus(Order::STATUS_CANCELLED);
        $order->setStripeSessionId(null);
        $this->entityManager->flush();
    }

    /**
     * Annule les commandes restées non payées au-delà du délai.
     *
     * @return int nombre de commandes annulées
     */
    public function releaseExpired(\DateTimeInterface $olderThan): int
    {
        $orders = $this->orderRepository->findPayableCreatedBefore($olderThan);
        foreach ($orders as $order) {
            $this->cancel($order);
        }

        return count($orders);
    }
}

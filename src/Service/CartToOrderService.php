<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use App\Exception\ProductsUnavailableException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Transforme le panier d'un client en commande et réserve les pièces uniques.
 */
class CartToOrderService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws ProductsUnavailableException si une pièce a été vendue entre-temps
     *                                      (elle est alors retirée du panier)
     */
    public function createOrderFromCart(User $user, Cart $cart): Order
    {
        if ($cart->getProduct()->isEmpty()) {
            throw new \LogicException('Le panier est vide.');
        }

        $unavailable = [];

        $order = $this->entityManager->wrapInTransaction(function () use ($user, $cart, &$unavailable): ?Order {
            // Verrou ligne par ligne : deux clients qui valident la même pièce
            // au même instant sont sérialisés, le second verra available = false.
            foreach ($cart->getProduct() as $product) {
                $this->entityManager->refresh($product, LockMode::PESSIMISTIC_WRITE);
                if (!$product->isAvailable()) {
                    $unavailable[] = $product;
                }
            }

            if ($unavailable) {
                foreach ($unavailable as $product) {
                    $cart->removeProduct($product);
                }

                return null;
            }

            $order = (new Order())
                ->setUser($user)
                ->setStatus(Order::STATUS_PENDING);

            $total = 0.0;
            foreach ($cart->getProduct() as $product) {
                $order->addOrderItem($this->createOrderItem($product));
                $total += (float) $product->getPrice();

                // Réservation immédiate pendant le paiement.
                $product->setAvailable(false);
            }

            $order->setTotal(number_format($total, 2, '.', ''));
            $this->entityManager->persist($order);
            $cart->getProduct()->clear();

            return $order;
        });

        if ($unavailable) {
            throw new ProductsUnavailableException($unavailable);
        }

        return $order;
    }

    private function createOrderItem(Product $product): OrderItem
    {
        return (new OrderItem())
            ->setProduct($product)
            ->setProductName($product->getName())
            ->setUnitPrice($product->getPrice())
            ->setQuantity(1)
            ->setTotalPrice($product->getPrice());
    }
}

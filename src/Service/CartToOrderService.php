<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class CartToOrderService
{
    private EntityManagerInterface $entityManager;
    private CartRepository $cartRepository;

    public function __construct(EntityManagerInterface $entityManager, CartRepository $cartRepository)
    {
        $this->entityManager = $entityManager;
        $this->cartRepository = $cartRepository;
    }

    public function createOrderFromCart(User $user, Cart $cart): ?Order
    {
        if ($cart->getProduct()->isEmpty()) {
            throw new \Exception('Le panier est vide.');
        }

        // Chaque pièce est unique : vérifier qu'aucune n'a été vendue entre-temps
        // (achetée par un autre client) avant de créer la commande.
        $unavailable = [];
        foreach ($cart->getProduct() as $product) {
            if (!$product->isAvailable()) {
                $unavailable[] = $product;
            }
        }
        if (!empty($unavailable)) {
            foreach ($unavailable as $product) {
                $cart->removeProduct($product);
            }
            $this->entityManager->flush();

            $names = implode(', ', array_map(fn ($p) => $p->getName(), $unavailable));
            throw new \Exception(sprintf(
                'Désolé, la/les pièce(s) suivante(s) viennent d\'être vendues et ont été retirées de votre panier : %s',
                $names
            ));
        }

        // Créer la commande
        $order = new Order();
        $order->setUser($user);
        $order->setStatus('pending');
        $totalPrice = 0;

        // Parcourir les produits du panier
        foreach ($cart->getProduct() as $product) {
            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setProductName($product->getName());
            $orderItem->setUnitPrice($product->getPrice());
            $orderItem->setQuantity(1);
            $orderItem->setTotalPrice($product->getPrice());

            $totalPrice += $orderItem->getTotalPrice();

            $orderItem->setCustomerOrder($order); // Relation avec la commande
            $order->addOrderItem($orderItem);

            // Réserver la pièce unique immédiatement pour empêcher qu'elle
            // soit vendue à quelqu'un d'autre pendant le paiement Stripe.
            $product->setAvailable(false);
        }

        $order->setTotal($totalPrice);

        // Enregistrer la commande dans la base de données
        $this->entityManager->persist($order);

        // Nettoyer le panier
        $cart->getProduct()->clear();

        $this->entityManager->flush();

        return $order;
    }
}

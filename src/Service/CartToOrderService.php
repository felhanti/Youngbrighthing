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

    public function createOrderFromCart(User $user,Cart $cart): ?Order
    {
        // Récupérer le panier actif de l'utilisateur
        $cart = $this->cartRepository->findOneBy(['user' => $user]);

        if (!$cart) {
            throw new \Exception('Aucun panier actif trouvé pour cet utilisateur.');
        }

        // Créer la commande
        $order = new Order();
        $order->setUser($user);
        $order->setStatus('pending');
        $totalPrice = 0;

        // Parcourir les produits du panier
        foreach ($cart->getProduct() as $product) {
            $orderItem = new OrderItem();
            $orderItem->setProductName($product->getName());
            $orderItem->setTotalPrice($product->getPrice());

            $totalPrice += $orderItem->getTotalPrice();

            $orderItem->setCustomerOrder($order); // Relation avec la commande
            $order->addOrderItem($orderItem);
        }

        $order->setTotal($totalPrice);

        // Enregistrer la commande dans la base de données
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        // Optionnel : Nettoyer le panier ou le marquer comme traité
        $cart->getProduct()->clear();
        $this->entityManager->flush();

        return $order;
    }
}

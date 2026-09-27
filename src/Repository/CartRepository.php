<?php

namespace App\Repository;

use App\Entity\Cart;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cart>
 */
class CartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class);
    }

    /** Chaque client a un seul panier, créé à la première utilisation. */
    public function getOrCreateForUser(User $user): Cart
    {
        $cart = $user->getCarts()->first() ?: null;
        if ($cart) {
            return $cart;
        }

        $cart = (new Cart())->setUser($user);
        $user->addCart($cart);
        $this->getEntityManager()->persist($cart);

        return $cart;
    }
}

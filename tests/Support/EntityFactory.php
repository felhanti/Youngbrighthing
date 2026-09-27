<?php

namespace App\Tests\Support;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Fabrique d'entités partagée par les tests (évite de recopier la même création d'utilisateur partout).
 */
trait EntityFactory
{
    protected function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function createUser(string $role = 'ROLE_USER'): User
    {
        $user = (new User())
            ->setEmail('test+'.uniqid('', true).'@example.com')
            ->setRoles([$role])
            ->setPassword('irrelevant-for-this-test')
            ->setAdress('1 rue de Test')
            ->setCP('75000')
            ->setCity('Paris')
            ->setCountry('France')
            ->setVerified(true);
        $user->setNom('Test')->setPrenom('User')->setBirthDate(new \DateTime('1990-01-01'));

        $this->em()->persist($user);
        $this->em()->flush();

        return $user;
    }

    protected function createProduct(bool $available = true, string $price = '100.00'): Product
    {
        $product = (new Product())
            ->setName('Pièce de test '.uniqid())
            ->setDescription('Pièce unique de test.')
            ->setPrice($price)
            ->setAvailable($available)
            ->setSize('M');

        $this->em()->persist($product);
        $this->em()->flush();

        return $product;
    }

    /** Commande « réservée » : la pièce est déjà indisponible, comme après CartToOrderService. */
    protected function createOrder(User $user, Product $product, string $status = Order::STATUS_PENDING): Order
    {
        $product->setAvailable(false);
        $order = (new Order())
            ->setUser($user)
            ->setStatus($status)
            ->setTotal($product->getPrice());
        $order->addOrderItem((new OrderItem())
            ->setProduct($product)
            ->setProductName($product->getName())
            ->setUnitPrice($product->getPrice())
            ->setQuantity(1)
            ->setTotalPrice($product->getPrice()));

        $this->em()->persist($order);
        $this->em()->flush();

        return $order;
    }
}

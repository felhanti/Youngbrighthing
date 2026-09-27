<?php

namespace App\Tests\Service;

use App\Entity\Cart;
use App\Entity\Product;
use App\Entity\User;
use App\Service\CartToOrderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CartToOrderServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private CartToOrderService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->service = $container->get(CartToOrderService::class);
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('client+'.uniqid().'@example.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('irrelevant-for-this-test');
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setBirthDate(new \DateTime('1990-01-01'));
        $user->setAdress('1 rue de Test');
        $user->setCP('75000');
        $user->setCity('Paris');
        $user->setCountry('France');
        $user->setVerified(true);

        $this->entityManager->persist($user);

        return $user;
    }

    private function createProduct(string $name, bool $available): Product
    {
        $product = new Product();
        $product->setName($name);
        $product->setDescription('Pièce unique de test.');
        $product->setPrice('100.00');
        $product->setAvailable($available);
        $product->setSize('M');

        $this->entityManager->persist($product);

        return $product;
    }

    public function testCreatingOrderReservesTheUniquePiece(): void
    {
        $user = $this->createUser();
        $product = $this->createProduct('Veste de test', true);

        $cart = new Cart();
        $cart->setUser($user);
        $cart->addProduct($product);
        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        $order = $this->service->createOrderFromCart($user, $cart);

        self::assertFalse($product->isAvailable(), 'La pièce doit être réservée (indisponible) dès la création de la commande.');
        self::assertCount(1, $order->getOrderItems());

        $orderItem = $order->getOrderItems()->first();
        self::assertSame($product, $orderItem->getProduct());
        self::assertSame('100.00', $orderItem->getUnitPrice());
        self::assertSame(1, $orderItem->getQuantity());
        self::assertCount(0, $cart->getProduct(), 'Le panier doit être vidé après la création de la commande.');
    }

    public function testCannotOrderAProductAlreadySold(): void
    {
        $user = $this->createUser();
        $product = $this->createProduct('Hoodie déjà vendu', false);

        $cart = new Cart();
        $cart->setUser($user);
        $cart->addProduct($product);
        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/vendues/');

        try {
            $this->service->createOrderFromCart($user, $cart);
        } finally {
            self::assertCount(0, $cart->getProduct(), 'La pièce indisponible doit être retirée du panier.');
        }
    }
}

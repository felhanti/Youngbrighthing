<?php

namespace App\Tests\Controller;

use App\Entity\Order;
use App\Tests\Support\EntityFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Un client ne doit jamais pouvoir agir sur la commande d'un autre.
 */
class OrderSecurityTest extends WebTestCase
{
    use EntityFactory;

    public function testCannotStartPaymentForSomeoneElsesOrder(): void
    {
        $client = static::createClient();
        $order = $this->createOrder($this->createUser(), $this->createProduct());

        $client->loginUser($this->createUser());
        $client->request('GET', '/order/'.$order->getId().'/pay');

        self::assertResponseStatusCodeSame(404);
        $this->em()->refresh($order);
        self::assertSame(Order::STATUS_PENDING, $order->getStatus(), 'Le statut ne doit pas avoir changé.');
    }

    public function testCannotViewSomeoneElsesOrder(): void
    {
        $client = static::createClient();
        $order = $this->createOrder($this->createUser(), $this->createProduct());

        $client->loginUser($this->createUser());
        $client->request('GET', '/order/summary/'.$order->getId());

        self::assertResponseStatusCodeSame(404);
    }

    public function testAnonymousCannotPayOrListOrders(): void
    {
        $client = static::createClient();
        $order = $this->createOrder($this->createUser(), $this->createProduct());

        $client->request('GET', '/order/'.$order->getId().'/pay');
        self::assertResponseRedirects('/login');

        $client->request('GET', '/orders');
        self::assertResponseRedirects('/login');
    }

    public function testFinalizingAnOrderRequiresPost(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createUser());

        $client->request('GET', '/order/finalize');

        self::assertResponseStatusCodeSame(405);
    }

    public function testCompletedOrderCannotBeSentBackToPayment(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $order = $this->createOrder($user, $this->createProduct(), Order::STATUS_COMPLETED);

        $client->loginUser($user);
        $client->request('GET', '/order/'.$order->getId().'/pay');

        self::assertResponseRedirects('/order/summary/'.$order->getId());
        $this->em()->refresh($order);
        self::assertSame(Order::STATUS_COMPLETED, $order->getStatus());
    }

    public function testStripeFailureCancelsOrderAndGivesPiecesBack(): void
    {
        // En test, STRIPE_SECRET_KEY est vide : la création de session échoue.
        $client = static::createClient();
        $user = $this->createUser();
        $product = $this->createProduct();
        $order = $this->createOrder($user, $product);

        $client->loginUser($user);
        $client->request('GET', '/order/'.$order->getId().'/pay');

        self::assertResponseRedirects('/cart');
        $this->em()->clear();
        $order = $this->em()->find(Order::class, $order->getId());
        self::assertSame(Order::STATUS_CANCELLED, $order->getStatus());
        $product = $order->getOrderItems()->first()->getProduct();
        self::assertTrue($product->isAvailable(), 'La pièce doit être remise en vente.');
        self::assertTrue($order->getUser()->getCarts()->first()->getProduct()->contains($product), 'La pièce doit revenir dans le panier.');
    }
}

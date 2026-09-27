<?php

namespace App\Tests\Controller;

use App\Entity\Order;
use App\Tests\Support\EntityFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminShippingTest extends WebTestCase
{
    use EntityFactory;

    public function testAdminShipsPaidOrderAndCustomerIsNotified(): void
    {
        $client = static::createClient();
        $order = $this->createOrder($this->createUser(), $this->createProduct(), Order::STATUS_COMPLETED);
        $client->loginUser($this->createUser('ROLE_ADMIN'));

        $crawler = $client->request('GET', '/admin/order/'.$order->getId());
        $client->submit($crawler->filter('form[action$="/ship"]')->form(['tracking_number' => '6A12345678901']));

        self::assertResponseRedirects('/admin/order/'.$order->getId());
        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertEmailAddressContains($email, 'To', $order->getUser()->getEmail());
        self::assertEmailHtmlBodyContains($email, '6A12345678901');

        $this->em()->clear();
        $order = $this->em()->find(Order::class, $order->getId());
        self::assertSame(Order::STATUS_SHIPPED, $order->getStatus());
        self::assertSame('6A12345678901', $order->getTrackingNumber());
    }

    public function testUnpaidOrderCannotBeShipped(): void
    {
        $client = static::createClient();
        $order = $this->createOrder($this->createUser(), $this->createProduct(), Order::STATUS_PENDING);
        $client->loginUser($this->createUser('ROLE_ADMIN'));

        $crawler = $client->request('GET', '/admin/order/'.$order->getId());

        self::assertCount(0, $crawler->filter('form[action$="/ship"]'), 'Pas de bouton d\'expédition sur une commande non payée.');
    }

    public function testAdminCanFilterOrdersByStatus(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createUser('ROLE_ADMIN'));

        $client->request('GET', '/admin/order?status=completed');

        self::assertResponseIsSuccessful();
    }
}

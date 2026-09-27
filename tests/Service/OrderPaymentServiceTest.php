<?php

namespace App\Tests\Service;

use App\Entity\Order;
use App\Service\OrderPaymentService;
use App\Tests\Support\EntityFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;

class OrderPaymentServiceTest extends KernelTestCase
{
    use EntityFactory;
    use MailerAssertionsTrait;

    public function testPaymentIsConfirmedOnceAndEmailsSentOnce(): void
    {
        self::bootKernel();
        $service = static::getContainer()->get(OrderPaymentService::class);
        $order = $this->createOrder($this->createUser(), $this->createProduct(), Order::STATUS_PROCESSING);

        // Le retour client et le webhook Stripe confirment la même commande.
        self::assertTrue($service->confirmPayment($order));
        self::assertFalse($service->confirmPayment($order));

        self::assertSame(Order::STATUS_COMPLETED, $order->getStatus());
        self::assertEmailCount(2); // client + boutique, une seule fois
        self::assertEmailAddressContains(self::getMailerMessage(0), 'To', $order->getUser()->getEmail());
        self::assertEmailHtmlBodyContains(self::getMailerMessage(0), '#'.$order->getId());
        self::assertEmailAddressContains(self::getMailerMessage(1), 'To', 'boutique@example.com');
    }

    public function testCancelledOrderIsNeverConfirmed(): void
    {
        self::bootKernel();
        $service = static::getContainer()->get(OrderPaymentService::class);
        $order = $this->createOrder($this->createUser(), $this->createProduct(), Order::STATUS_CANCELLED);

        self::assertFalse($service->confirmPayment($order));
        self::assertSame(Order::STATUS_CANCELLED, $order->getStatus());
        self::assertEmailCount(0);
    }
}

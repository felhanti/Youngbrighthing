<?php

namespace App\Tests\Service;

use App\Entity\Order;
use App\Service\OrderReservationService;
use App\Tests\Support\EntityFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OrderReservationServiceTest extends KernelTestCase
{
    use EntityFactory;

    private OrderReservationService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(OrderReservationService::class);
    }

    public function testExpiredUnpaidOrdersAreCancelledAndPiecesReleased(): void
    {
        $product = $this->createProduct();
        $order = $this->createOrder($this->createUser(), $product, Order::STATUS_PROCESSING);
        $order->setCreatedAt(new \DateTime('-2 hours'));
        $this->em()->flush();

        $this->service->releaseExpired(new \DateTimeImmutable('-45 minutes'));

        self::assertSame(Order::STATUS_CANCELLED, $order->getStatus());
        self::assertTrue($product->isAvailable());
    }

    public function testRecentOrdersAreLeftAlone(): void
    {
        $product = $this->createProduct();
        $order = $this->createOrder($this->createUser(), $product);

        $this->service->releaseExpired(new \DateTimeImmutable('-45 minutes'));

        self::assertSame(Order::STATUS_PENDING, $order->getStatus());
        self::assertFalse($product->isAvailable());
    }

    public function testCompletedOrderIsNeverCancelled(): void
    {
        $product = $this->createProduct();
        $order = $this->createOrder($this->createUser(), $product, Order::STATUS_COMPLETED);

        $this->service->cancel($order);

        self::assertSame(Order::STATUS_COMPLETED, $order->getStatus());
        self::assertFalse($product->isAvailable(), 'Une pièce payée ne doit jamais être remise en vente.');
    }
}

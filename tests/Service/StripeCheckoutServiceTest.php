<?php

namespace App\Tests\Service;

use App\Entity\Order;
use App\Service\StripeCheckoutService;
use PHPUnit\Framework\TestCase;
use Stripe\Checkout\Session;

/**
 * Une session Stripe ne doit valider QUE la commande pour laquelle elle a été créée.
 */
class StripeCheckoutServiceTest extends TestCase
{
    private function order(int $id, string $total, string $sessionId): Order
    {
        $order = (new Order())->setTotal($total)->setStripeSessionId($sessionId);
        (new \ReflectionProperty(Order::class, 'id'))->setValue($order, $id);

        return $order;
    }

    private function session(array $overrides = []): Session
    {
        return Session::constructFrom($overrides + [
            'id' => 'cs_test_A',
            'payment_status' => 'paid',
            'amount_total' => 12000,
            'metadata' => ['order_id' => '42'],
        ]);
    }

    public function testPaidSessionOfTheOrderIsAccepted(): void
    {
        $service = new StripeCheckoutService('sk_test_x');

        self::assertTrue($service->isPaidSessionFor($this->session(), $this->order(42, '120.00', 'cs_test_A')));
    }

    public function testSessionPaidForAnotherOrderIsRejected(): void
    {
        $service = new StripeCheckoutService('sk_test_x');
        // Scénario d'attaque : payer une petite commande et rejouer sa session sur une grosse.
        $bigOrder = $this->order(99, '480.00', 'cs_test_B');

        self::assertFalse($service->isPaidSessionFor($this->session(), $bigOrder));
    }

    public function testUnpaidOrWrongAmountSessionIsRejected(): void
    {
        $service = new StripeCheckoutService('sk_test_x');
        $order = $this->order(42, '120.00', 'cs_test_A');

        self::assertFalse($service->isPaidSessionFor($this->session(['payment_status' => 'unpaid']), $order));
        self::assertFalse($service->isPaidSessionFor($this->session(['amount_total' => 100]), $order));
    }

    public function testNotConfiguredWithoutKey(): void
    {
        self::assertFalse((new StripeCheckoutService(''))->isConfigured());
    }
}

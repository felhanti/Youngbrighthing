<?php

namespace App\Service;

use App\Entity\Order;
use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Point d'entrée unique vers Stripe Checkout.
 */
class StripeCheckoutService
{
    /** Pièces uniques : fenêtre de paiement courte pour libérer vite la réservation. */
    private const SESSION_LIFETIME = 1800;

    private ?StripeClient $client = null;

    public function __construct(
        #[Autowire('%env(STRIPE_SECRET_KEY)%')]
        private readonly string $secretKey,
    ) {
    }

    public function isConfigured(): bool
    {
        return '' !== $this->secretKey;
    }

    public function createSession(Order $order, string $successUrl, string $cancelUrl): Session
    {
        return $this->client()->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $order->getTotalInCents(),
                    'product_data' => ['name' => 'Commande #'.$order->getId()],
                ],
            ]],
            'customer_email' => $order->getUser()?->getEmail(),
            'client_reference_id' => (string) $order->getId(),
            'metadata' => ['order_id' => (string) $order->getId()],
            'expires_at' => time() + self::SESSION_LIFETIME,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);
    }

    public function retrieveSession(string $sessionId): Session
    {
        return $this->client()->checkout->sessions->retrieve($sessionId);
    }

    /**
     * Une session ne prouve le paiement d'une commande que si elle est payée,
     * rattachée à cette commande et du bon montant.
     */
    public function isPaidSessionFor(Session $session, Order $order): bool
    {
        return 'paid' === $session->payment_status
            && $session->id === $order->getStripeSessionId()
            && (string) ($session->metadata['order_id'] ?? '') === (string) $order->getId()
            && (int) $session->amount_total === $order->getTotalInCents();
    }

    private function client(): StripeClient
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Clé Stripe absente (STRIPE_SECRET_KEY).');
        }

        return $this->client ??= new StripeClient($this->secretKey);
    }
}

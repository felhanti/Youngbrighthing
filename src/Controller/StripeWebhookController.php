<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Event;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class StripeWebhookController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature', '');
        $webhookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException|SignatureVerificationException $e) {
            $this->logger->warning('Webhook Stripe rejeté : signature/payload invalide.', ['error' => $e->getMessage()]);

            return new Response('Signature invalide', 400);
        }

        match ($event->type) {
            'checkout.session.completed' => $this->onCheckoutCompleted($event),
            'checkout.session.expired' => $this->onCheckoutExpired($event),
            default => null,
        };

        return new Response('OK', 200);
    }

    private function onCheckoutCompleted(Event $event): void
    {
        $session = $event->data->object;
        $order = $this->findOrderFromSession($session);

        if (!$order) {
            return;
        }

        // Idempotent : ignore si déjà marquée comme terminée (Stripe peut renvoyer l'événement plusieurs fois).
        if ($order->getStatus() === 'completed') {
            return;
        }

        if ($session->payment_status === 'paid') {
            $order->setStatus('completed');
            $this->entityManager->flush();
        }
    }

    private function onCheckoutExpired(Event $event): void
    {
        $session = $event->data->object;
        $order = $this->findOrderFromSession($session);

        if (!$order || $order->getStatus() !== 'processing') {
            return;
        }

        // Le client n'a pas payé à temps : on libère les pièces uniques réservées
        // pour qu'elles redeviennent achetables par un autre client.
        foreach ($order->getOrderItems() as $orderItem) {
            $product = $orderItem->getProduct();
            if ($product && !$product->getIssold()) {
                $product->setIssold(true);
            }
        }

        $order->setStatus('cancelled');
        $this->entityManager->flush();
    }

    private function findOrderFromSession(object $session): ?Order
    {
        $orderId = $session->metadata->order_id ?? null;
        if (!$orderId) {
            return null;
        }

        return $this->entityManager->getRepository(Order::class)->find((int) $orderId);
    }
}

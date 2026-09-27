<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\OrderPaymentService;
use App\Service\OrderReservationService;
use App\Service\StripeCheckoutService;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StripeWebhookController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly OrderPaymentService $payments,
        private readonly OrderReservationService $reservationService,
        private readonly StripeCheckoutService $stripe,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(STRIPE_WEBHOOK_SECRET)%')]
        private readonly string $webhookSecret,
    ) {
    }

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function handle(Request $request): Response
    {
        if ('' === $this->webhookSecret) {
            $this->logger->error('Webhook Stripe reçu mais STRIPE_WEBHOOK_SECRET est vide.');

            return new Response('Webhook non configuré', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->headers->get('Stripe-Signature', ''),
                $this->webhookSecret,
            );
        } catch (\UnexpectedValueException|SignatureVerificationException $e) {
            $this->logger->warning('Webhook Stripe rejeté : signature/payload invalide.', ['error' => $e->getMessage()]);

            return new Response('Signature invalide', Response::HTTP_BAD_REQUEST);
        }

        $session = $event->data->object;
        if ($session instanceof Session) {
            match ($event->type) {
                'checkout.session.completed' => $this->onCompleted($session),
                'checkout.session.expired' => $this->onExpired($session),
                default => null,
            };
        }

        return new Response('OK');
    }

    private function onCompleted(Session $session): void
    {
        $order = $this->findOrder($session);

        // Idempotent : Stripe peut renvoyer le même événement plusieurs fois.
        if ($order && Order::STATUS_PROCESSING === $order->getStatus() && $this->stripe->isPaidSessionFor($session, $order)) {
            $this->payments->confirmPayment($order);
        }
    }

    private function onExpired(Session $session): void
    {
        $order = $this->findOrder($session);

        // On ne réagit qu'à la session en cours : si le client a relancé un paiement,
        // l'expiration de l'ancienne session ne doit pas annuler la commande.
        if ($order && $order->getStripeSessionId() === $session->id) {
            $this->reservationService->cancel($order);
        }
    }

    private function findOrder(Session $session): ?Order
    {
        $orderId = $session->metadata['order_id'] ?? null;

        return $orderId ? $this->orderRepository->find((int) $orderId) : null;
    }
}

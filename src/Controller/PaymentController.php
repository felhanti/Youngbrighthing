<?php

namespace App\Controller;

use App\Entity\Order;
use App\Service\OrderReservationService;
use App\Service\StripeCheckoutService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class PaymentController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly StripeCheckoutService $stripe,
        private readonly OrderReservationService $reservationService,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/order/{id}/pay', name: 'payment_stripe', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function checkout(Order $order): Response
    {
        if ($order->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException();
        }

        if (!$order->isPayable()) {
            return $this->redirectToRoute('order_summary', ['id' => $order->getId()]);
        }

        try {
            $session = $this->stripe->createSession(
                $order,
                $this->generateUrl('order_summary', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL).'?session_id={CHECKOUT_SESSION_ID}',
                $this->generateUrl('app_cart_show', [], UrlGeneratorInterface::ABSOLUTE_URL),
            );
        } catch (\Throwable $e) {
            $this->logger->error('Création de session Stripe impossible.', ['order' => $order->getId(), 'error' => $e->getMessage()]);
            // L'échec vient de nous : on rend les pièces au client plutôt que de les bloquer.
            $this->reservationService->cancel($order, restoreToCart: true);
            $this->addFlash('danger', 'Le paiement est momentanément indisponible. Vos articles sont toujours dans votre panier, réessayez dans quelques minutes.');

            return $this->redirectToRoute('app_cart_show');
        }

        $order->setStatus(Order::STATUS_PROCESSING);
        $order->setStripeSessionId($session->id);
        $this->entityManager->flush();

        return $this->redirect($session->url, Response::HTTP_SEE_OTHER);
    }
}
